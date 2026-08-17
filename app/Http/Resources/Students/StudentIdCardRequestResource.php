<?php

declare(strict_types=1);

namespace App\Http\Resources\Students;

use App\Models\Students\StudentIdCardRequest;
use App\Support\Students\StudentIdCardFace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StudentIdCardRequest
 */
class StudentIdCardRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $student = $this->student;
        $face = StudentIdCardFace::fromStudent($student);

        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'statusLabel' => $this->status?->label(),
            'reason' => $this->reason?->value,
            'reasonLabel' => $this->reason?->label(),
            'notes' => $this->notes,
            'rejectionReason' => $this->rejection_reason,
            'serialNumber' => $this->serial_number,
            'photoUrl' => $this->photoUrl('card'),
            'photoThumbUrl' => $this->photoUrl('thumb'),
            'requiresFee' => $this->reason?->requiresFee() ?? false,
            'feeAmount' => $this->reason?->requiresFee() ? (float) config('id_cards.reissue_fee') : 0,
            'feeLedgerId' => $this->fee_ledger_id,
            'reviewedAt' => $this->reviewed_at?->toIso8601String(),
            'printedAt' => $this->printed_at?->toIso8601String(),
            'issuedAt' => $this->issued_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'student' => [
                'id' => $student?->id,
                'name' => $face->studentName,
                ...$face->toArray(),
            ],
            'reviewerName' => $this->reviewer?->full_name,
            'printerName' => $this->printer?->full_name,
            'issuerName' => $this->issuer?->full_name,
        ];
    }
}
