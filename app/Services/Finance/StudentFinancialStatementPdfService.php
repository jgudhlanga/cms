<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Helpers\DateHelper;
use App\Helpers\DocumentHelper;
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
                'enrolmentStatus' => $enrolment?->studentEnrolmentStatus?->name,
            ], fn (?string $value) => filled($value)),
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
                    'latestEnrolment.semester',
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
            'latestEnrolment.semester',
            'latestEnrolment.studentEnrolmentStatus',
        ]);

        return $student;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return list<array{transactionDate: string, description: string, source: string, debit: string, credit: string, runningBalance: string}>
     */
    private function ledgerRows(Collection $entries): array
    {
        return $entries->map(function (array $line): array {
            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);
            $source = (string) ($line['source'] ?? 'bank');
            $sourceKey = match ($source) {
                'online' => 'finance.source_online',
                'assessed' => 'finance.source_assessed',
                default => 'finance.source_bank',
            };

            return [
                'transactionDate' => $this->displayValue(
                    DateHelper::formatDate($line['transaction_date'] ?? null, 'd/m/Y')
                    ?? $line['transaction_date']
                ),
                'description' => $this->displayValue($line['description'] ?? $line['narration'] ?? null),
                'source' => __($sourceKey),
                'debit' => $debit > 0 ? $this->formatUsd($debit) : '—',
                'credit' => $credit > 0 ? $this->formatUsd($credit) : '—',
                'runningBalance' => $this->formatUsd((string) ($line['running_balance'] ?? '0')),
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
