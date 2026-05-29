<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinanceTransactionQueryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'financeTransactionQuery',
            'id' => $this->resource->id,
            'attributes' => [
                'studentId' => $this->resource->student_id,
                'studentName' => $this->resource->student?->user?->full_name,
                'studentNumber' => $this->resource->student?->student_number,
                'paymentReference' => $this->resource->payment_reference,
                'description' => $this->resource->description,
                'status' => $this->resource->status?->value,
                'statusLabel' => $this->resource->status?->label(),
                'declineReason' => $this->resource->decline_reason,
                'bankStatementId' => $this->resource->bank_statement_id,
                'reconciledByName' => $this->resource->reconciler?->full_name,
                'declinedByName' => $this->resource->decliner?->full_name,
                'reconciledAt' => $this->resource->reconciled_at?->toISOString(),
                'declinedAt' => $this->resource->declined_at?->toISOString(),
                'createdAt' => $this->resource->created_at?->toISOString(),
                'updatedAt' => $this->resource->updated_at?->toISOString(),
            ],
        ];
    }
}
