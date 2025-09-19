<?php

namespace App\Http\Resources\Integrations;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class LedgerResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'ledgers',
            'id' => $this->id,
            "attributes" => [
                'feeTypeId' => $this->fee_type_id,
                'feeType' => $this->feeType?->name,
                'paymentOption' => $this->payment_option,
                'type' => $this->type,
                'paymentStatus' => $this->payment_status,
                'amount' => $this->amount,
                'currency' => $this->currency,
                'clientFee' => $this->client_fee,
                'merchantFee' => $this->merchant_fee,
                'systemReference' => $this->system_reference,
                'paymentReference' => $this->payment_reference,
                'dueDate' => $this->due_date ? Carbon::parse($this->due_date)->format('Y-m-d') : null,
                'paymentDate' => $this->payment_date ? Carbon::parse($this->payment_date)->format('Y-m-d') : null,
                'responseMessage' => $this->response_message,
                'responseCode' => $this->response_code,
                'levelId' => $this->level_id,
                'level' => $this->level?->name,
                'studentProgramId' => $this->student_program_id,
                'createdAt' => Carbon::parse($this->created_at)->format('Y-m-d'),
                'updatedAt' => Carbon::parse($this->updated_at)->format('Y-m-d'),
                'deletedAt' => $this->deleted_at ? Carbon::parse($this->deleted_at)->format('Y-m-d') : null,
            ]
        ];
    }
}
