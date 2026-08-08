<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\Shared\FeeTypeEnum;
use App\Helpers\DateHelper;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\FeeType;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Services\Students\StudentFeeClearanceService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StudentLedgerService
{
    public function __construct(
        private readonly StudentFeeClearanceService $feeClearanceService,
    ) {}

    /**
     * @return array{
     *     entries: Collection<int, array<string, mixed>>,
     *     summary: array{
     *         totalInvoiced: string,
     *         totalPayments: string,
     *         outstandingBalance: string,
     *         paidPercent: float
     *     }
     * }
     */
    public function build(Student $student): array
    {
        $lines = $this->feeStructureInvoiceLines($student)
            ->concat($this->bankLines($student))
            ->concat($this->tuitionLedgerReceiptLines($student))
            ->sort(function (array $left, array $right): int {
                $dateCompare = strcmp(
                    (string) ($left['sort_date'] ?? ''),
                    (string) ($right['sort_date'] ?? '')
                );

                if ($dateCompare !== 0) {
                    return $dateCompare;
                }

                return strcmp((string) $left['id'], (string) $right['id']);
            })
            ->values();

        $runningBalance = 0.0;
        $totalInvoiced = 0.0;
        $totalPayments = 0.0;

        $entries = $lines->map(function (array $line) use (&$runningBalance, &$totalInvoiced, &$totalPayments): array {
            $debit = (float) $line['debit'];
            $credit = (float) $line['credit'];

            $totalInvoiced += $debit;
            $totalPayments += $credit;
            $runningBalance += $debit - $credit;

            $line['running_balance'] = number_format($runningBalance, 2, '.', '');
            unset($line['sort_date']);

            return $line;
        });

        $outstandingBalance = $totalInvoiced - $totalPayments;
        $paidPercent = $totalInvoiced > 0
            ? round(($totalPayments / $totalInvoiced) * 100, 1)
            : 0.0;

        return [
            'entries' => $entries,
            'summary' => [
                'totalInvoiced' => number_format($totalInvoiced, 2, '.', ''),
                'totalPayments' => number_format($totalPayments, 2, '.', ''),
                'outstandingBalance' => number_format($outstandingBalance, 2, '.', ''),
                'paidPercent' => $paidPercent,
            ],
        ];
    }

    public function hasRecordedPayments(Student $student): bool
    {
        return (float) $this->build($student)['summary']['totalPayments'] > 0;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function feeStructureInvoiceLines(Student $student): Collection
    {
        if (! $student->exists) {
            return collect();
        }

        $breakdown = $this->feeClearanceService->expectedBreakdown($student);
        $assessmentDate = $this->resolveAssessmentDate($student);

        return collect($breakdown)
            ->filter(fn (array $item): bool => (float) ($item['amount'] ?? 0) > 0)
            ->values()
            ->map(function (array $item, int $index) use ($assessmentDate): array {
                $amount = round((float) $item['amount'], 2);
                $key = (string) $item['key'];
                $label = (string) $item['label'];
                $description = $label.' · '.__('finance.assessed_fee_invoice');

                return [
                    'id' => 'assessed:'.$key,
                    'source' => 'assessed',
                    'sort_date' => $assessmentDate->copy()->addSeconds($index)->toDateTimeString(),
                    'transaction_date' => DateHelper::formatDate($assessmentDate),
                    'description' => $description,
                    'narration' => $description,
                    'reference' => null,
                    'transaction_id' => null,
                    'transaction_details' => null,
                    'debit' => number_format($amount, 2, '.', ''),
                    'credit' => number_format(0, 2, '.', ''),
                    'debit_credit_flag' => 'D',
                    'iso_currency_code' => 'USD',
                    'usd_conversion_rate' => null,
                    'usd_conversion_rate_label' => null,
                    'usd_conversion_rate_date' => null,
                    'original_amount_credit' => null,
                    'original_amount_debit' => null,
                    'original_iso_currency_code' => null,
                ];
            });
    }

    private function resolveAssessmentDate(Student $student): CarbonInterface
    {
        $enrolment = $student->latestEnrolment()->first();
        if ($enrolment?->created_at !== null) {
            return Carbon::instance($enrolment->created_at)->startOfDay();
        }

        $application = $student->latestApplication()->first();
        if ($application?->created_at !== null) {
            return Carbon::instance($application->created_at)->startOfDay();
        }

        return now()->startOfDay();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function bankLines(Student $student): Collection
    {
        return $this->studentStatementQuery($student)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get()
            ->map(function (ZBBankStatement $statement): array {
                $debit = (float) ($statement->amountDebitInUsd() ?? $statement->amount_debit ?? 0);
                $credit = (float) ($statement->amountCreditInUsd() ?? $statement->amount_credit ?? 0);
                $usdConversionRateMetadata = $statement->usdConversionRateMetadata();
                $hasUsdConversion = $usdConversionRateMetadata !== null;
                $isoCurrencyCode = $statement->iso_currency_code;

                if ($statement->hasZwgCurrencyCode() && ($statement->amountCreditInUsd() !== null || $statement->amountDebitInUsd() !== null)) {
                    $isoCurrencyCode = 'USD';
                }

                $description = trim((string) ($statement->narration ?: $statement->transaction_details ?: $statement->description));

                return [
                    'id' => 'bank:'.$statement->id,
                    'source' => 'bank',
                    'sort_date' => (string) $statement->transaction_date,
                    'transaction_date' => DateHelper::formatDate($statement->transaction_date),
                    'description' => $description,
                    'narration' => $statement->narration,
                    'reference' => $statement->reference,
                    'transaction_id' => $statement->transaction_id,
                    'transaction_details' => $statement->transaction_details,
                    'debit' => number_format($debit, 2, '.', ''),
                    'credit' => number_format($credit, 2, '.', ''),
                    'debit_credit_flag' => $statement->debit_credit_flag,
                    'iso_currency_code' => $isoCurrencyCode,
                    'usd_conversion_rate' => $usdConversionRateMetadata['rate'] ?? null,
                    'usd_conversion_rate_label' => $usdConversionRateMetadata['label'] ?? null,
                    'usd_conversion_rate_date' => $usdConversionRateMetadata['date'] ?? null,
                    'original_amount_credit' => $hasUsdConversion ? $statement->amount_credit : null,
                    'original_amount_debit' => $hasUsdConversion ? $statement->amount_debit : null,
                    'original_iso_currency_code' => $hasUsdConversion ? $statement->iso_currency_code : null,
                ];
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function tuitionLedgerReceiptLines(Student $student): Collection
    {
        $tuitionFeeType = FeeType::query()
            ->where('slug', FeeTypeEnum::TUITION_FEE->slug())
            ->first();

        if ($tuitionFeeType === null) {
            return collect();
        }

        $applicationIds = $student->exists
            ? $student->applications()->pluck('id')
            : collect();

        if ($applicationIds->isEmpty()) {
            return collect();
        }

        $feeTypeName = $tuitionFeeType->name ?: FeeTypeEnum::TUITION_FEE->name();

        return $this->tuitionLedgerReceiptQuery((int) $tuitionFeeType->id, $applicationIds)
            ->orderBy('id')
            ->get()
            ->map(function (Ledger $ledger) use ($feeTypeName): array {
                $amount = round((float) $ledger->amount, 2);
                $transactionDate = $this->resolveLedgerTransactionDate($ledger);
                $reference = trim((string) ($ledger->payment_reference ?: $ledger->system_reference ?: ''));
                $label = __('finance.online_tuition_payment');
                $description = $reference !== ''
                    ? $feeTypeName.' · '.$label.' · '.$reference
                    : $feeTypeName.' · '.$label;

                return [
                    'id' => 'ledger:'.$ledger->id,
                    'source' => 'online',
                    'sort_date' => $transactionDate?->toDateTimeString() ?? '',
                    'transaction_date' => DateHelper::formatDate($transactionDate),
                    'description' => $description,
                    'narration' => $description,
                    'reference' => $reference !== '' ? $reference : null,
                    'transaction_id' => $ledger->system_reference,
                    'transaction_details' => null,
                    'debit' => number_format(0, 2, '.', ''),
                    'credit' => number_format($amount, 2, '.', ''),
                    'debit_credit_flag' => 'C',
                    'iso_currency_code' => strtoupper((string) ($ledger->currency ?: 'USD')),
                    'usd_conversion_rate' => null,
                    'usd_conversion_rate_label' => null,
                    'usd_conversion_rate_date' => null,
                    'original_amount_credit' => null,
                    'original_amount_debit' => null,
                    'original_iso_currency_code' => null,
                ];
            });
    }

    /**
     * @param  Collection<int, int|string>  $applicationIds
     */
    private function tuitionLedgerReceiptQuery(int $tuitionFeeTypeId, Collection $applicationIds): Builder
    {
        return Ledger::query()
            ->where('fee_type_id', $tuitionFeeTypeId)
            ->where('type', 'receipt')
            ->where('payment_status', 'paid')
            ->where(function (Builder $query) use ($applicationIds): void {
                $query->whereIn('student_application_id', $applicationIds)
                    ->orWhere(function (Builder $ledgerableQuery) use ($applicationIds): void {
                        $ledgerableQuery
                            ->where('ledgerable_type', StudentApplication::class)
                            ->whereIn('ledgerable_id', $applicationIds);
                    });
            });
    }

    private function resolveLedgerTransactionDate(Ledger $ledger): ?CarbonInterface
    {
        if ($ledger->payment_date !== null) {
            return $ledger->payment_date;
        }

        if ($ledger->due_date !== null) {
            return $ledger->due_date;
        }

        return $ledger->created_at;
    }

    private function studentStatementQuery(Student $student): Builder
    {
        $studentStatementMatchPatterns = StudentBankStatementMatchPatterns::forStudent($student);
        $exactLikePatterns = $studentStatementMatchPatterns['exactLikePatterns'];

        if ($exactLikePatterns === []) {
            return ZBBankStatement::query()->where('id', 0);
        }

        return ZBBankStatement::query()->where(function (Builder $statementQuery) use ($exactLikePatterns): void {
            foreach ($exactLikePatterns as $pattern) {
                $statementQuery->orWhere(function (Builder $fieldQuery) use ($pattern): void {
                    $fieldQuery
                        ->where('narration', 'like', $pattern)
                        ->orWhere('pipe5_details', 'like', $pattern)
                        ->orWhere('pipe10_details', 'like', $pattern)
                        ->orWhere('transaction_details', 'like', $pattern);
                });
            }
        });
    }
}
