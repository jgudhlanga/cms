<?php

namespace App\Http\Resources\Finance;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentPaymentReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $amountCreditInUsd = $this->amountCreditInUsd();
        $amountDebitInUsd = $this->amountDebitInUsd();
        $usdConversionRateMetadata = $this->usdConversionRateMetadata();
        $hasUsdConversion = $usdConversionRateMetadata !== null;

        $isoCurrencyCode = $this->iso_currency_code;

        if ($this->hasZwgCurrencyCode() && ($amountCreditInUsd !== null || $amountDebitInUsd !== null)) {
            $isoCurrencyCode = 'USD';
        }

        return [
            'type' => 'student-payment-receipt',
            'id' => $this->id,
            'attributes' => [
                'tranNumberAsc' => $this->tran_number_asc,
                'tranNumberDesc' => $this->tran_number_desc,
                'transactionId' => $this->transaction_id,
                'transactionSrId' => $this->transaction_sr_id,
                'transactionDate' => DateHelper::formatDate($this->transaction_date),
                'narration' => $this->narration,
                'reference' => $this->reference,
                'code' => $this->code,
                'description' => $this->description,
                'debitCreditFlag' => $this->debit_credit_flag,
                'amountCredit' => $amountCreditInUsd,
                'amountDebit' => $amountDebitInUsd,
                'usdConversionRate' => $usdConversionRateMetadata['rate'] ?? null,
                'usdConversionRateLabel' => $usdConversionRateMetadata['label'] ?? null,
                'usdConversionRateDate' => $usdConversionRateMetadata['date'] ?? null,
                'originalAmountCredit' => $hasUsdConversion ? $this->amount_credit : null,
                'originalAmountDebit' => $hasUsdConversion ? $this->amount_debit : null,
                'originalIsoCurrencyCode' => $hasUsdConversion ? $this->iso_currency_code : null,
                'clearedRunningBalance' => $this->cleared_running_balance,
                'blockedBalance' => $this->blocked_balance,
                'debitLimit' => $this->debit_limit,
                'creditLimit' => $this->credit_limit,
                'isoCurrencyCode' => $isoCurrencyCode,
                'accountDescription' => $this->account_description,
                'ubfullName' => $this->ubfull_name,
                'pipeCount' => $this->pipe_count,
                'pipe1' => $this->pipe1,
                'pipe2' => $this->pipe2,
                'pipe3' => $this->pipe3,
                'pipe4' => $this->pipe4,
                'pipe5' => $this->pipe5,
                'pipe6' => $this->pipe6,
                'pipe7' => $this->pipe7,
                'pipe8' => $this->pipe8,
                'pipe9' => $this->pipe9,
                'pipe10' => $this->pipe10,
                'pipe1Details' => $this->pipe1_details,
                'pipe2Details' => $this->pipe2_details,
                'pipe3Details' => $this->pipe3_details,
                'pipe4Details' => $this->pipe4_details,
                'pipe5Details' => $this->pipe5_details,
                'pipe6Details' => $this->pipe6_details,
                'pipe7Details' => $this->pipe7_details,
                'pipe8Details' => $this->pipe8_details,
                'pipe9Details' => $this->pipe9_details,
                'pipe10Details' => $this->pipe10_details,
                'transactionDetails' => $this->transaction_details,
                'createdAt' => DateHelper::formatDate($this->created_at),
                'updatedAt' => DateHelper::formatDate($this->updated_at),
                'deletedAt' => DateHelper::formatDate($this->deleted_at),
            ],
        ];
    }
}
