<?php

declare(strict_types=1);

namespace App\Services\Enrolments;

use App\Models\Integrations\Banks\ZBBankStatement;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

class StudentBankPaymentMatcher
{
    private function singleQueryThreshold(): int
    {
        return (int) config('custom.enrolments.bulk_finalise.payment_match_single_query_threshold', 10);
    }

    private function statementChunkSize(): int
    {
        return (int) config('custom.enrolments.bulk_finalise.payment_match_statement_chunk_size', 500);
    }

    /**
     * @return array{start_date: CarbonImmutable, end_date: CarbonImmutable}
     */
    public function resolveDefaultDateRange(): array
    {
        $timezone = (string) config('app.timezone');
        $defaultStartDate = (string) config('custom.bank-statements.plan_anchor_start');

        return [
            'start_date' => CarbonImmutable::parse($defaultStartDate, $timezone)->startOfDay(),
            'end_date' => CarbonImmutable::parse(Carbon::now($timezone)->toDateString(), $timezone)->endOfDay(),
        ];
    }

    public function hasPaymentInRange(
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
            ->where(function ($statementQuery) use ($studentNumberPattern): void {
                $statementQuery
                    ->where('narration', 'like', $studentNumberPattern)
                    ->orWhere('pipe5_details', 'like', $studentNumberPattern)
                    ->orWhere('pipe10_details', 'like', $studentNumberPattern)
                    ->orWhere('transaction_details', 'like', $studentNumberPattern);
            })
            ->exists();
    }

    /**
     * @param  list<string>  $studentNumbers
     * @return array<string, bool>
     */
    public function hasPaymentForAny(
        array $studentNumbers,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): array {
        $normalizedNumbers = $this->normalizeStudentNumbers($studentNumbers);

        if ($normalizedNumbers === []) {
            return [];
        }

        if (count($normalizedNumbers) <= $this->singleQueryThreshold()) {
            $results = array_fill_keys($normalizedNumbers, false);

            foreach ($normalizedNumbers as $studentNumber) {
                $results[$studentNumber] = $this->hasPaymentInRange($studentNumber, $startDate, $endDate);
            }

            return $results;
        }

        return $this->matchStudentNumbersInRange($normalizedNumbers, $startDate, $endDate);
    }

    /**
     * @param  list<string>  $studentNumbers
     * @return array<string, bool>
     */
    public function matchStudentNumbersInRange(
        array $studentNumbers,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): array {
        $normalizedNumbers = $this->normalizeStudentNumbers($studentNumbers);
        $results = array_fill_keys($normalizedNumbers, false);

        if ($normalizedNumbers === []) {
            return $results;
        }

        /** @var array<string, true> $unresolved */
        $unresolved = array_fill_keys($normalizedNumbers, true);

        ZBBankStatement::query()
            ->where('debit_credit_flag', 'C')
            ->whereBetween('transaction_date', [
                $startDate->toDateTimeString(),
                $endDate->toDateTimeString(),
            ])
            ->select(['id', 'narration', 'pipe5_details', 'pipe10_details', 'transaction_details'])
            ->orderBy('id')
            ->lazyById($this->statementChunkSize())
            ->each(function (ZBBankStatement $statement) use (&$unresolved, &$results): bool {
                if ($unresolved === []) {
                    return false;
                }

                $haystack = implode(' ', array_filter([
                    $statement->narration,
                    $statement->pipe5_details,
                    $statement->pipe10_details,
                    $statement->transaction_details,
                ], fn (?string $value): bool => is_string($value) && $value !== ''));

                if ($haystack === '') {
                    return true;
                }

                foreach (array_keys($unresolved) as $studentNumber) {
                    if (str_contains($haystack, $studentNumber)) {
                        $results[$studentNumber] = true;
                        unset($unresolved[$studentNumber]);
                    }
                }

                return true;
            });

        return $results;
    }

    /**
     * @param  list<string>  $studentNumbers
     * @return list<string>
     */
    private function normalizeStudentNumbers(array $studentNumbers): array
    {
        return collect($studentNumbers)
            ->filter(fn (string $number): bool => $number !== '')
            ->unique()
            ->values()
            ->all();
    }
}
