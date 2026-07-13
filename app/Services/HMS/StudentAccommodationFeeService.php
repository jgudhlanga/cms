<?php

namespace App\Services\HMS;

use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Models\HMS\HostelApplication;
use App\Models\Institution\FeeStructure;
use App\Models\Ledgers\Ledger;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
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
            'latestEnrolment.studentApplication.intakePeriod',
            'latestEnrolment.studentApplication.departmentLevel.level',
        ]);

        $studentApplication = $student->latestEnrolment?->studentApplication;
        $calendarYear = $student->latestEnrolment?->studentApplication?->intakePeriod?->calendar_year;
        $intakeLabel = $studentApplication?->intakePeriod?->name ?? $calendarYear;

        $ledgers = $this->accommodationLedgers($student, $studentApplication);
        $receipts = $ledgers->where('type', 'receipt');

        $total = (float) ($this->resolveFeeStructureForStudent($student)?->local_fca_amount ?? 0);
        $paid = (float) $receipts
            ->where('payment_status', 'paid')
            ->sum(fn (Ledger $ledger) => (float) $ledger->amount);

        $due = max(0, $total - $paid);

        $isFullyPaid = $this->studentHasPaidAccommodationFee($student, $studentApplication);

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

    public function openAwaitingPaymentApplication(Student $student): ?HostelApplication
    {
        return HostelApplication::query()
            ->where('student_id', $student->id)
            ->whereIn('status', [
                HostelApplicationStatusEnum::AWAITING_PAYMENT,
                HostelApplicationStatusEnum::PARTIALLY_PAID,
            ])
            ->latest()
            ->first();
    }

    public function feeStructureForStudent(Student $student): ?FeeStructure
    {
        $student->loadMissing([
            'latestEnrolment.studentApplication.departmentLevel.level',
        ]);

        return $this->feeStructureForStudentApplication($student->latestEnrolment?->studentApplication);
    }

    public function feeStructureForStudentApplication(?StudentApplication $studentApplication): ?FeeStructure
    {
        if ($studentApplication === null) {
            return null;
        }

        $studentApplication->loadMissing(['departmentLevel.level']);

        $levelId = $studentApplication->departmentLevel?->level?->id;

        if ($levelId === null) {
            return null;
        }

        return FeeStructure::query()
            ->where('tenant_id', $studentApplication->tenant_id)
            ->where('level_id', $levelId)
            ->whereRelation('feeType', 'slug', FeeTypeEnum::STUDENT_ACCOMMODATION_FEE->slug())
            ->first();
    }

    public function resolveFeeStructureForStudent(Student $student, ?HostelApplication $openApplication = null): ?FeeStructure
    {
        $feeStructure = $this->feeStructureForStudent($student);

        if ($feeStructure !== null) {
            return $feeStructure;
        }

        $openApplication ??= $this->openAwaitingPaymentApplication($student);

        if ($openApplication !== null) {
            $openApplication->loadMissing('studentEnrolment.studentApplication.departmentLevel.level');

            $feeStructure = $this->feeStructureForStudentApplication(
                $openApplication->studentEnrolment?->studentApplication,
            );

            if ($feeStructure !== null) {
                return $feeStructure;
            }
        }

        return $this->defaultAccommodationFeeStructure((int) $student->tenant_id);
    }

    public function defaultAccommodationFeeStructure(int $tenantId): ?FeeStructure
    {
        $baseQuery = FeeStructure::query()
            ->where('tenant_id', $tenantId)
            ->whereRelation('feeType', 'slug', FeeTypeEnum::STUDENT_ACCOMMODATION_FEE->slug());

        return (clone $baseQuery)
            ->whereNull('level_id')
            ->first()
            ?? $baseQuery->first();
    }

    public function amountDueForStudent(Student $student, ?FeeStructure $feeStructure = null): string
    {
        return $this->summaryForStudent($student)['due'];
    }

    private function studentHasPaidAccommodationFee(Student $student, ?StudentApplication $studentApplication): bool
    {
        if ($studentApplication?->hasPaid(FeeTypeEnum::STUDENT_ACCOMMODATION_FEE)) {
            return true;
        }

        return HostelApplication::query()
            ->where('student_id', $student->id)
            ->get()
            ->contains(fn (HostelApplication $application) => $application->hasPaidAccommodationFee());
    }

    /**
     * @return Collection<int, Ledger>
     */
    private function accommodationLedgers(Student $student, ?StudentApplication $studentApplication): Collection
    {
        $accommodationSlugs = [
            FeeTypeEnum::STUDENT_ACCOMMODATION_FEE->slug(),
            FeeTypeEnum::GUEST_ACCOMMODATION_FEE->slug(),
        ];

        $ledgers = collect();

        if ($studentApplication?->student?->user !== null) {
            $ledgers = $studentApplication->student->user
                ->ledgers()
                ->with('feeType')
                ->whereHas('feeType', fn ($query) => $query->whereIn('slug', $accommodationSlugs))
                ->orderByDesc('created_at')
                ->get();
        }

        $applicationLedgers = HostelApplication::query()
            ->where('student_id', $student->id)
            ->get()
            ->flatMap(fn (HostelApplication $application) => $application->ledgerTransactions()
                ->with('feeType')
                ->whereHas('feeType', fn ($query) => $query->whereIn('slug', $accommodationSlugs))
                ->get());

        return $ledgers
            ->merge($applicationLedgers)
            ->sortByDesc(fn (Ledger $ledger) => $ledger->created_at)
            ->values();
    }
}
