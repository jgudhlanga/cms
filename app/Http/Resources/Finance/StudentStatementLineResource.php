<?php

declare(strict_types=1);

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read array<string, mixed> $resource
 */
class StudentStatementLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $line */
        $line = $this->resource;

        return [
            'type' => 'student-statement-line',
            'id' => $line['id'],
            'attributes' => [
                'source' => $line['source'],
                'transactionDate' => $line['transaction_date'],
                'narration' => $line['narration'] ?? $line['description'],
                'description' => $line['description'],
                'reference' => $line['reference'] ?? null,
                'transactionId' => $line['transaction_id'] ?? null,
                'transactionDetails' => $line['transaction_details'] ?? null,
                'debitCreditFlag' => $line['debit_credit_flag'],
                'amountCredit' => $line['credit'],
                'amountDebit' => $line['debit'],
                'runningBalance' => $line['running_balance'] ?? null,
                'clearedRunningBalance' => $line['running_balance'] ?? null,
                'isoCurrencyCode' => $line['iso_currency_code'] ?? 'USD',
                'usdConversionRate' => $line['usd_conversion_rate'] ?? null,
                'usdConversionRateLabel' => $line['usd_conversion_rate_label'] ?? null,
                'usdConversionRateDate' => $line['usd_conversion_rate_date'] ?? null,
                'originalAmountCredit' => $line['original_amount_credit'] ?? null,
                'originalAmountDebit' => $line['original_amount_debit'] ?? null,
                'originalIsoCurrencyCode' => $line['original_iso_currency_code'] ?? null,
            ],
        ];
    }
}
