<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Helpers\DateHelper;
use App\Helpers\DocumentHelper;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Students\Student;
use Illuminate\Support\Collection;

class StudentFinancialStatementPdfService
{
    public function __construct(
        private readonly StudentLedgerService $studentLedgerService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function assemble(Student $student): array
    {
        $student = $this->loadStudent($student);
        $ledger = $this->studentLedgerService->build($student);
        $enrolment = $student->latestEnrolment;

        $studentName = $student->user?->full_name ?? '';
        $summary = $ledger['summary'];

        return [
            'documentTemplate' => DocumentHelper::resolvePdfHeaderTemplate($student->tenant_id),
            'generatedAt' => now()->format('d M Y'),
            'studentName' => $studentName,
            'studentNumber' => $student->student_number ?? '',
            'identityLabelKey' => $student->isZimbabwean() ? 'trans.id_number' : 'trans.passport_number',
            'identityValue' => $this->displayValue(
                $student->isZimbabwean() ? $student->id_number : $student->passport_number,
            ),
            'profileSummary' => array_filter([
                'course' => $enrolment?->departmentCourse?->course?->name,
                'level' => $enrolment?->departmentLevel?->level?->name,
                'department' => $enrolment?->institutionDepartment?->department?->name,
                'modeOfStudy' => $enrolment?->modeOfStudy?->name,
                'academicCalendar' => $enrolment?->academicCalendar?->calendar_year,
                //'academicYearOption' => $enrolment?->academicYearOption?->name,
                'enrolmentStatus' => $enrolment?->studentEnrolmentStatus?->name,
            ], fn (?string $value) => filled($value)),
            'summary' => [
                'totalInvoiced' => $this->formatUsd($summary['totalInvoiced']),
                'totalPayments' => $this->formatUsd($summary['totalPayments']),
                'outstandingBalance' => $this->formatUsd($summary['outstandingBalance']),
                'paidPercent' => (string) $summary['paidPercent'].'%',
            ],
            'ledgerRows' => $this->ledgerRows($ledger['entries']),
        ];
    }

    private function loadStudent(Student $student): Student
    {
        if ($student->exists) {
            return Student::query()
                ->with([
                    'user',
                    'latestEnrolment.institutionDepartment.department',
                    'latestEnrolment.departmentLevel.level',
                    'latestEnrolment.departmentCourse.course',
                    'latestEnrolment.modeOfStudy',
                    'latestEnrolment.academicCalendar',
                    'latestEnrolment.academicYearOption',
                    'latestEnrolment.studentEnrolmentStatus',
                ])
                ->findOrFail($student->id);
        }

        $student->loadMissing([
            'user',
            'latestEnrolment.institutionDepartment.department',
            'latestEnrolment.departmentLevel.level',
            'latestEnrolment.departmentCourse.course',
            'latestEnrolment.modeOfStudy',
            'latestEnrolment.academicCalendar',
            'latestEnrolment.academicYearOption',
            'latestEnrolment.studentEnrolmentStatus',
        ]);

        return $student;
    }

    /**
     * @param  Collection<int, ZBBankStatement>  $entries
     * @return list<array{transactionDate: string, description: string, debit: string, credit: string, runningBalance: string}>
     */
    private function ledgerRows(Collection $entries): array
    {
        return $entries->map(function (ZBBankStatement $statement): array {
            $debit = $statement->amountDebitInUsd();
            $credit = $statement->amountCreditInUsd();

            return [
                'transactionDate' => $this->displayValue(DateHelper::formatDate($statement->transaction_date, 'd/m/Y')),
                'description' => $this->displayValue($statement->narration ?: $statement->transaction_details),
                'debit' => $debit !== null && $debit !== '' ? $this->formatUsd($debit) : '—',
                'credit' => $credit !== null && $credit !== '' ? $this->formatUsd($credit) : '—',
                'runningBalance' => $this->formatUsd((string) ($statement->computed_running_balance ?? '0')),
            ];
        })->all();
    }

    private function displayValue(mixed $value): string
    {
        if ($value === null) {
            return '---';
        }

        $stringValue = trim((string) $value);

        return $stringValue === '' ? '---' : $stringValue;
    }

    private function formatUsd(string|float|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }

        $numericAmount = (float) $amount;

        if (! is_finite($numericAmount)) {
            return '—';
        }

        $formatted = number_format($numericAmount, 2, '.', '');

        return str_starts_with($formatted, '-') ? '-USD$'.ltrim($formatted, '-') : 'USD$'.$formatted;
    }
}
