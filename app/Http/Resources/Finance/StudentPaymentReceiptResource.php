<?php

namespace App\Http\Resources\Finance;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentPaymentReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'bank' => $this->bank,
            'amount' => $this->amount,
            'transaction_created_date' => DateHelper::formatDate($this->transaction_created_date),
            'narrative' => $this->narrative,
            'nr1' => $this->nr1,
            'nr2' => $this->nr2,
            'nr3' => $this->nr3,
            'nr4' => $this->nr4,
            'picked' => $this->picked,
            'reference' => $this->reference,
            'source' => $this->source,
            'status' => $this->status,
            'tcd' => $this->tcd,
            'currency' => $this->currency,
            'transaction_date' => DateHelper::formatDate($this->transaction_date),
            'created_at' => DateHelper::formatDate($this->created_at),
            'updated_at' => DateHelper::formatDate($this->updated_at),
            'deleted_at' => DateHelper::formatDate($this->deleted_at),
        ];
    }
}
