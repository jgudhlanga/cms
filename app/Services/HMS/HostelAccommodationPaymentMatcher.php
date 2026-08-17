<?php

declare(strict_types=1);

namespace App\Services\HMS;

use App\Enums\Shared\FeeTypeEnum;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Ledgers\Ledger;
use App\Models\Students\Student;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class HostelAccommodationPaymentMatcher
{
    public const SOURCE_LEDGER = 'ledger';

    public const SOURCE_BANK = 'bank';

    public function __construct(
        protected HostelApplicationSemesterService $semesterService,
    ) {}

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}|null
     */
    public function semesterWindowForStudent(Student $student): ?array
    {
        $dates = $this->semesterService->datesForApplication($student);

        if (! $dates['success'] || $dates['checkIn'] === null || $dates['checkOut'] === null) {
            return null;
        }

        $timezone = (string) config('app.timezone');

        return [
            'start' => CarbonImmutable::parse($dates['checkIn'], $timezone)->startOfDay(),
            'end' => CarbonImmutable::parse($dates['checkOut'], $timezone)->endOfDay(),
        ];
    }

    /**
     * @return self::SOURCE_LEDGER|self::SOURCE_BANK|null
     */
    public function evidenceSource(Student $student): ?string
    {
        $window = $this->semesterWindowForStudent($student);

        if ($window === null) {
            return $this->hasLedgerEvidence($student, null) ? self::SOURCE_LEDGER : null;
        }

        if ($this->hasLedgerEvidence($student, $window)) {
            return self::SOURCE_LEDGER;
        }

        $studentNumber = trim((string) $student->student_number);

        if ($studentNumber !== '' && $this->hasBankEvidence($studentNumber, $window['start'], $window['end'])) {
            return self::SOURCE_BANK;
        }

        return null;
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable}|null  $window
     */
    public function hasLedgerEvidence(Student $student, ?array $window): bool
    {
        $student->loadMissing(['user', 'hostelApplications']);

        $ledgerableIdsByType = [];

        if ($student->user !== null) {
            $ledgerableIdsByType[$student->user->getMorphClass()][] = (int) $student->user->id;
        }

        foreach ($student->hostelApplications as $application) {
            $ledgerableIdsByType[$application->getMorphClass()][] = (int) $application->id;
        }

        if ($ledgerableIdsByType === []) {
            return false;
        }

        $query = Ledger::query()
            ->where('type', 'receipt')
            ->where('payment_status', 'paid')
            ->where(function (Builder $ledgerQuery) use ($ledgerableIdsByType): void {
                foreach ($ledgerableIdsByType as $type => $ids) {
                    $ledgerQuery->orWhere(function (Builder $morphQuery) use ($type, $ids): void {
                        $morphQuery
                            ->where('ledgerable_type', $type)
                            ->whereIn('ledgerable_id', array_values(array_unique($ids)));
                    });
                }
            })
            ->where(function (Builder $descriptionQuery): void {
                $slugs = [
                    FeeTypeEnum::STUDENT_ACCOMMODATION_FEE->slug(),
                    FeeTypeEnum::GUEST_ACCOMMODATION_FEE->slug(),
                ];

                $descriptionQuery
                    ->whereHas('feeType', fn (Builder $feeTypeQuery) => $feeTypeQuery->whereIn('slug', $slugs))
                    ->orWhereRaw('LOWER(COALESCE(payment_reference, \'\')) LIKE ?', ['%accommodation%'])
                    ->orWhereRaw('LOWER(COALESCE(response_message, \'\')) LIKE ?', ['%accommodation%']);
            });

        if ($window !== null) {
            $query->where(function (Builder $dateQuery) use ($window): void {
                $dateQuery
                    ->whereBetween('payment_date', [
                        $window['start']->toDateTimeString(),
                        $window['end']->toDateTimeString(),
                    ])
                    ->orWhere(function (Builder $createdQuery) use ($window): void {
                        $createdQuery
                            ->whereNull('payment_date')
                            ->whereBetween('created_at', [
                                $window['start']->toDateTimeString(),
                                $window['end']->toDateTimeString(),
                            ]);
                    });
            });
        }

        return $query->exists();
    }

    public function hasBankEvidence(
        string $studentNumber,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): bool {
        if ($studentNumber === '') {
            return false;
        }

        $escapedStudentNumber = addcslashes($studentNumber, '\%_');
        $studentNumberPattern = "%{$escapedStudentNumber}%";

        return ZBBankStatement::query()
            ->where('debit_credit_flag', 'C')
            ->whereBetween('transaction_date', [
                $startDate->toDateTimeString(),
                $endDate->toDateTimeString(),
            ])
            ->where(function (Builder $statementQuery) use ($studentNumberPattern): void {
                $statementQuery
                    ->where('narration', 'like', $studentNumberPattern)
                    ->orWhere('pipe5_details', 'like', $studentNumberPattern)
                    ->orWhere('pipe10_details', 'like', $studentNumberPattern)
                    ->orWhere('transaction_details', 'like', $studentNumberPattern);
            })
            ->where(function (Builder $accommodationQuery): void {
                $accommodationQuery
                    ->whereRaw('LOWER(COALESCE(narration, \'\')) LIKE ?', ['%accommodation%'])
                    ->orWhereRaw('LOWER(COALESCE(pipe5_details, \'\')) LIKE ?', ['%accommodation%'])
                    ->orWhereRaw('LOWER(COALESCE(pipe10_details, \'\')) LIKE ?', ['%accommodation%'])
                    ->orWhereRaw('LOWER(COALESCE(transaction_details, \'\')) LIKE ?', ['%accommodation%'])
                    ->orWhereRaw('LOWER(COALESCE(description, \'\')) LIKE ?', ['%accommodation%']);
            })
            ->exists();
    }
}
