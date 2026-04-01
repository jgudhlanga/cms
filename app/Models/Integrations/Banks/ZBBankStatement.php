<?php

namespace App\Models\Integrations\Banks;

use App\Models\Finance\FinanceExchangeRate;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 */
class ZBBankStatement extends Model
{
    use Filterable, LogsActivity, Paginatable, SoftDeletes;

    private const USD_CURRENCY_CODE = 'USD';

    private const ZWG_CURRENCY_CODE = 'ZWG';

    private const CONVERSION_SCALE = 8;

    protected $table = 'zb_bank_statements';

    protected $fillable = [
        'tran_number_asc',
        'tran_number_desc',
        'transaction_id',
        'transaction_sr_id',
        'transaction_date',
        'narration',
        'reference',
        'code',
        'description',
        'debit_credit_flag',
        'amount_credit',
        'amount_debit',
        'cleared_running_balance',
        'blocked_balance',
        'debit_limit',
        'credit_limit',
        'iso_currency_code',
        'account_description',
        'ubfull_name',
        'pipe_count',
        'pipe1',
        'pipe2',
        'pipe3',
        'pipe4',
        'pipe5',
        'pipe6',
        'pipe7',
        'pipe8',
        'pipe9',
        'pipe10',
        'pipe1_details',
        'pipe2_details',
        'pipe3_details',
        'pipe4_details',
        'pipe5_details',
        'pipe6_details',
        'pipe7_details',
        'pipe8_details',
        'pipe9_details',
        'pipe10_details',
        'transaction_details',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('ZBBankStatement')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function amountCreditInUsd(): ?string
    {
        return $this->convertZwgAmountToUsd($this->amount_credit);
    }

    public function amountDebitInUsd(): ?string
    {
        return $this->convertZwgAmountToUsd($this->amount_debit);
    }

    public function usdConversionRateMetadata(): ?array
    {
        if (! $this->isZwgCurrency()) {
            return null;
        }

        $exchangeRate = $this->resolveFinanceExchangeRateForUsdConversion();

        if ($exchangeRate === null) {
            return null;
        }

        $rate = trim((string) $exchangeRate->rate);

        if (! is_numeric($rate) || (float) $rate <= 0.0) {
            return null;
        }

        return [
            'rate' => $rate,
            'label' => "ZWG/USD @ {$rate}",
            'date' => (string) $exchangeRate->date,
        ];
    }

    public function convertZwgAmountToUsd(?string $amount): ?string
    {
        if ($amount === null || trim($amount) === '') {
            return null;
        }

        if (! $this->isZwgCurrency()) {
            return $amount;
        }

        $exchangeRate = $this->resolveFinanceExchangeRateForUsdConversion();

        if ($exchangeRate === null) {
            return null;
        }

        $rate = trim((string) $exchangeRate->rate);

        if (! is_numeric($rate) || (float) $rate <= 0.0) {
            return null;
        }

        return $this->formatToTwoDecimalPlaces(
            bcdiv($amount, $rate, self::CONVERSION_SCALE)
        );
    }

    public function resolveFinanceExchangeRateForUsdConversion(): ?FinanceExchangeRate
    {
        $transactionDate = $this->normalizedTransactionDateForRateLookup();

        if ($transactionDate === null) {
            return null;
        }

        $sameDayRate = FinanceExchangeRate::query()
            ->where('date', $transactionDate)
            ->where(fn (Builder $query): Builder => $this->applyUsdZwgCurrencyPairConstraint($query))
            ->orderByDesc('id')
            ->first();

        if ($sameDayRate !== null) {
            return $sameDayRate;
        }

        return FinanceExchangeRate::query()
            ->whereDate('date', '<=', $transactionDate)
            ->where(fn (Builder $query): Builder => $this->applyUsdZwgCurrencyPairConstraint($query))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();
    }

    private function normalizedTransactionDateForRateLookup(): ?string
    {
        $transactionDate = trim((string) $this->transaction_date);

        if ($transactionDate === '') {
            return null;
        }

        try {
            return Carbon::parse($transactionDate)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function isZwgCurrency(): bool
    {
        return strtoupper((string) $this->iso_currency_code) === self::ZWG_CURRENCY_CODE;
    }

    public function hasZwgCurrencyCode(): bool
    {
        return $this->isZwgCurrency();
    }

    private function formatToTwoDecimalPlaces(string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function applyUsdZwgCurrencyPairConstraint(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $pairQuery): void {
                $pairQuery
                    ->where('currency_from', self::ZWG_CURRENCY_CODE)
                    ->where('currency_to', self::USD_CURRENCY_CODE);
            })
            ->orWhere(function (Builder $pairQuery): void {
                $pairQuery
                    ->where('currency_from', self::USD_CURRENCY_CODE)
                    ->where('currency_to', self::ZWG_CURRENCY_CODE);
            });
    }
}
