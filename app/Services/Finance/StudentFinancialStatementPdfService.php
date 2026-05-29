<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Helpers\DateHelper;
use App\Helpers\DocumentHelper;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Shared\Address;
use App\Models\Shared\Contact;
use App\Models\Shared\NextOfKin;
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
        $mainContact = $student->contacts->firstWhere('contact_is_main', 1);
        $mainAddress = $student->addresses->firstWhere('address_is_main', 1);
        $nextOfKin = $student->nextOfKins->first();

        $studentName = $student->user?->full_name ?? '';
        $summary = $ledger['summary'];

        return [
            'documentTemplate' => DocumentHelper::resolvePdfHeaderTemplate($student->tenant_id),
            'generatedAt' => now()->format('d M Y'),
            'studentName' => $studentName,
            'studentNumber' => $student->student_number ?? '',
            'profileSummary' => array_filter([
                'course' => $enrolment?->departmentCourse?->course?->name,
                'level' => $enrolment?->departmentLevel?->level?->name,
                'department' => $enrolment?->institutionDepartment?->department?->name,
                'modeOfStudy' => $enrolment?->modeOfStudy?->name,
                'academicCalendar' => $enrolment?->academicCalendar?->calendar_year,
                'academicYearOption' => $enrolment?->academicYearOption?->name,
                'enrolmentStatus' => $enrolment?->studentEnrolmentStatus?->name,
            ], fn (?string $value) => filled($value)),
            'personalInformation' => $this->personalInformationRows($student),
            'contactInformation' => $this->contactInformationRows($student, $mainContact, $mainAddress, $nextOfKin),
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
                    'title',
                    'gender',
                    'maritalStatus',
                    'race',
                    'idType',
                    'country',
                    'religion',
                    'contacts',
                    'addresses',
                    'nextOfKins.contacts',
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
            'title',
            'gender',
            'maritalStatus',
            'race',
            'idType',
            'country',
            'religion',
            'contacts',
            'addresses',
            'nextOfKins.contacts',
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
     * @return list<array{label: string, value: string}>
     */
    private function personalInformationRows(Student $student): array
    {
        $rows = [
            ['label' => 'Student Number', 'value' => $this->displayValue($student->student_number)],
            ['label' => 'Title', 'value' => $this->displayValue($student->title?->name)],
            ['label' => 'Gender', 'value' => $this->displayValue($student->gender?->title)],
            ['label' => 'Marital Status', 'value' => $this->displayValue($student->maritalStatus?->title)],
            ['label' => 'Identity Type', 'value' => $this->displayValue($student->idType?->name)],
        ];

        if ($student->isZimbabwean()) {
            $rows[] = ['label' => 'ID Number', 'value' => $this->displayValue($student->id_number)];
        } else {
            $rows[] = ['label' => 'Passport Number', 'value' => $this->displayValue($student->passport_number)];
            $rows[] = ['label' => 'Country', 'value' => $this->displayValue($student->country?->name)];
        }

        $rows[] = ['label' => 'Date of Birth', 'value' => $this->displayValue(DateHelper::formatDate($student->date_of_birth, 'd/m/Y'))];
        $rows[] = ['label' => 'Disability', 'value' => $this->disabilityLabel($student->disability_status)];
        $rows[] = ['label' => 'Race', 'value' => $this->displayValue($student->race?->title)];
        $rows[] = ['label' => 'Religion', 'value' => $this->displayValue($student->religion?->name)];
        $rows[] = ['label' => 'Denomination', 'value' => $this->displayValue($student->denomination)];
        $rows[] = ['label' => 'Weight', 'value' => $this->displayValue($student->weight)];
        $rows[] = ['label' => 'Height', 'value' => $this->displayValue($student->height)];

        return $rows;
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function contactInformationRows(
        Student $student,
        ?Contact $mainContact,
        ?Address $mainAddress,
        ?NextOfKin $nextOfKin,
    ): array {
        return [
            ['label' => 'Phone', 'value' => $this->displayValue($mainContact?->phone_number)],
            ['label' => 'Email', 'value' => $this->displayValue($student->user?->email)],
            ['label' => 'Home Address', 'value' => $this->formatAddress($mainAddress)],
            ['label' => 'Guardian', 'value' => $this->displayValue($nextOfKin?->name)],
            ['label' => 'Guardian Contact', 'value' => $this->displayValue($this->guardianPhone($nextOfKin))],
        ];
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

    private function guardianPhone(?NextOfKin $nextOfKin): ?string
    {
        if ($nextOfKin === null) {
            return null;
        }

        $contact = $nextOfKin->contacts->first();

        return $contact?->phone_number ?: $contact?->alt_phone_number;
    }

    private function formatAddress(?Address $address): string
    {
        if ($address === null) {
            return '---';
        }

        $parts = array_values(array_unique(array_filter([
            $address->address_1,
            $address->address_2,
            $address->address_3,
            $address->address_4,
            $address->address_5,
            $address->address_6,
        ], fn (?string $part) => filled(trim((string) $part)))));

        return $parts === [] ? '---' : implode(', ', $parts);
    }

    private function disabilityLabel(?string $status): string
    {
        return match ($status) {
            'yes' => 'Yes',
            'no' => 'No',
            'prefer_not_to_say' => 'Prefer not to say',
            default => $this->displayValue($status),
        };
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
