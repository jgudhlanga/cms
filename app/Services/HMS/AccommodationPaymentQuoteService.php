<?php

namespace App\Services\HMS;

use App\Enums\Integrations\PaymentCurrencyCodeEnum;
use App\Models\Finance\FinanceExchangeRate;
use App\Models\Institution\FeeStructure;
use App\Models\Students\Student;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class AccommodationPaymentQuoteService
{
    private const USD_CURRENCY_CODE = 'USD';

    private const ZWG_CURRENCY_CODE = 'ZWG';

    private const CONVERSION_SCALE = 8;

    public function __construct(
        protected StudentAccommodationFeeService $feeService,
    ) {}

    /**
     * @return array{
     *     usdDue: string,
     *     zwgDue: string|null,
     *     exchangeRate: array{rate: string, date: string, label: string}|null
     * }
     */
    public function previewForStudent(Student $student, ?FeeStructure $feeStructure = null): array
    {
        $usdDue = $this->usdDue($student, $feeStructure);
        $exchangeRate = $this->latestUsdZwgExchangeRate();

        return [
            'usdDue' => $usdDue,
            'zwgDue' => $exchangeRate !== null ? $this->convertUsdToZwg($usdDue, $exchangeRate) : null,
            'exchangeRate' => $this->exchangeRateMetadata($exchangeRate),
        ];
    }

    /**
     * @return array{
     *     selectedCurrency: string,
     *     currencyCode: string,
     *     paymentAmount: string,
     *     usdDue: string,
     *     exchangeRate: array{rate: string, date: string, label: string}|null
     * }|null
     */
    public function quoteForCurrency(Student $student, string $currency, ?FeeStructure $feeStructure = null): ?array
    {
        $currencyEnum = PaymentCurrencyCodeEnum::tryFromSelection($currency);

        if ($currencyEnum === null) {
            return null;
        }

        $usdDue = $this->usdDue($student, $feeStructure);

        if ($currencyEnum === PaymentCurrencyCodeEnum::Usd) {
            return [
                'selectedCurrency' => $currencyEnum->selectionValue(),
                'currencyCode' => $currencyEnum->value,
                'paymentAmount' => $usdDue,
                'usdDue' => $usdDue,
                'exchangeRate' => null,
            ];
        }

        $exchangeRate = $this->latestUsdZwgExchangeRate();

        if ($exchangeRate === null) {
            return null;
        }

        return [
            'selectedCurrency' => $currencyEnum->selectionValue(),
            'currencyCode' => $currencyEnum->value,
            'paymentAmount' => $this->convertUsdToZwg($usdDue, $exchangeRate),
            'usdDue' => $usdDue,
            'exchangeRate' => $this->exchangeRateMetadata($exchangeRate),
        ];
    }

    public function usdDue(Student $student, ?FeeStructure $feeStructure = null): string
    {
        return $this->feeService->amountDueForStudent($student, $feeStructure);
    }

    public function latestUsdZwgExchangeRate(): ?FinanceExchangeRate
    {
        $lookupDate = Carbon::today()->format('Y-m-d');

        $sameDayRate = FinanceExchangeRate::query()
            ->where('date', $lookupDate)
            ->where(fn (Builder $query): Builder => $this->applyUsdZwgCurrencyPairConstraint($query))
            ->orderByDesc('id')
            ->first();

        if ($sameDayRate !== null) {
            return $sameDayRate;
        }

        return FinanceExchangeRate::query()
            ->whereDate('date', '<=', $lookupDate)
            ->where(fn (Builder $query): Builder => $this->applyUsdZwgCurrencyPairConstraint($query))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();
    }

    public function convertUsdToZwg(string $usdAmount, FinanceExchangeRate $exchangeRate): string
    {
        $rate = trim((string) $exchangeRate->rate);

        if (! is_numeric($rate) || (float) $rate <= 0.0) {
            return '0.00';
        }

        return $this->formatToTwoDecimalPlaces(
            bcmul($usdAmount, $rate, self::CONVERSION_SCALE)
        );
    }

    /**
     * @return array{rate: string, date: string, label: string}|null
     */
    private function exchangeRateMetadata(?FinanceExchangeRate $exchangeRate): ?array
    {
        if ($exchangeRate === null) {
            return null;
        }

        $rate = trim((string) $exchangeRate->rate);

        if (! is_numeric($rate) || (float) $rate <= 0.0) {
            return null;
        }

        return [
            'rate' => $rate,
            'date' => (string) $exchangeRate->date,
            'label' => "ZWG/USD @ {$rate}",
        ];
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

    private function formatToTwoDecimalPlaces(string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
