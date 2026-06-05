<?php

namespace App\Services\HMS;

use App\Enums\Shared\FeeTypeEnum;
use App\Models\Institution\FeeStructure;
use App\Models\Ledgers\Ledger;
use App\Models\Students\Student;
use App\Models\Students\StudentProgram;
use Illuminate\Support\Collection;

class StudentAccommodationFeeService
{
    /**
     * @return array{
     *     calendarYear: string|null,
     *     intakeLabel: string|null,
     *     total: string,
     *     paid: string,
     *     due: string,
     *     isFullyPaid: bool,
     *     paymentHistory: list<array{date: string|null, amount: string, description: string|null}>
     * }
     */
    public function summaryForStudent(Student $student): array
    {
        $student->loadMissing([
            'latestEnrolment.studentProgram.intakePeriod',
            'latestEnrolment.studentProgram.departmentLevel.level',
            'latestEnrolment.studentProgram.modeOfStudy',
        ]);

        $studentProgram = $student->latestEnrolment?->studentProgram;
        $calendarYear = $student->latestEnrolment?->studentProgram?->intakePeriod?->calendar_year;
        $intakeLabel = $studentProgram?->intakePeriod?->name ?? $calendarYear;

        $ledgers = $this->accommodationLedgers($studentProgram);
        $invoices = $ledgers->where('type', 'invoice');
        $receipts = $ledgers->where('type', 'receipt');

        $total = (float) $invoices->sum(fn (Ledger $ledger) => (float) $ledger->amount);
        $paid = (float) $receipts
            ->where('payment_status', 'paid')
            ->sum(fn (Ledger $ledger) => (float) $ledger->amount);

        if ($total <= 0.0) {
            $total = (float) ($this->feeStructureForStudentProgram($studentProgram)?->local_fca_amount ?? 0);
        }

        $due = max(0, $total - $paid);

        $isFullyPaid = $studentProgram !== null
            ? $studentProgram->hasPaid(FeeTypeEnum::STUDENT_ACCOMMODATION_FEE)
            : $due <= 0 && $total > 0;

        if ($total === 0.0 && $isFullyPaid) {
            $paid = $paid > 0 ? $paid : 0.0;
        }

        $paymentHistory = $receipts
            ->where('payment_status', 'paid')
            ->sortByDesc(fn (Ledger $ledger) => $ledger->payment_date ?? $ledger->created_at)
            ->map(fn (Ledger $ledger): array => [
                'date' => $ledger->payment_date?->toDateString() ?? $ledger->created_at?->toDateString(),
                'amount' => number_format((float) $ledger->amount, 2, '.', ''),
                'description' => $ledger->payment_gateway
                    ?? $ledger->payment_reference
                    ?? $ledger->response_message,
            ])
            ->values()
            ->all();

        return [
            'calendarYear' => $calendarYear,
            'intakeLabel' => $intakeLabel,
            'total' => number_format($total, 2, '.', ''),
            'paid' => number_format($paid, 2, '.', ''),
            'due' => number_format($due, 2, '.', ''),
            'isFullyPaid' => $isFullyPaid,
            'paymentHistory' => $paymentHistory,
        ];
    }

    public function feeStructureForStudent(Student $student): ?FeeStructure
    {
        $student->loadMissing([
            'latestEnrolment.studentProgram.departmentLevel.level',
            'latestEnrolment.studentProgram.modeOfStudy',
        ]);

        return $this->feeStructureForStudentProgram($student->latestEnrolment?->studentProgram);
    }

    public function feeStructureForStudentProgram(?StudentProgram $studentProgram): ?FeeStructure
    {
        if ($studentProgram === null) {
            return null;
        }

        $studentProgram->loadMissing(['departmentLevel.level', 'modeOfStudy']);

        $levelId = $studentProgram->departmentLevel?->level?->id;
        $modeOfStudyId = $studentProgram->mode_of_study_id;

        if ($levelId === null || $modeOfStudyId === null) {
            return null;
        }

        return FeeStructure::query()
            ->where('tenant_id', $studentProgram->tenant_id)
            ->where('level_id', $levelId)
            ->where('mode_of_study_id', $modeOfStudyId)
            ->whereRelation('feeType', 'slug', FeeTypeEnum::STUDENT_ACCOMMODATION_FEE->slug())
            ->first();
    }

    /**
     * @return Collection<int, Ledger>
     */
    private function accommodationLedgers(?StudentProgram $studentProgram): Collection
    {
        if ($studentProgram === null || $studentProgram->student?->user === null) {
            return collect();
        }

        return $studentProgram->student->user
            ->ledgers()
            ->with('feeType')
            ->whereRelation('feeType', 'slug', FeeTypeEnum::STUDENT_ACCOMMODATION_FEE->slug())
            ->orderByDesc('created_at')
            ->get();
    }
}
