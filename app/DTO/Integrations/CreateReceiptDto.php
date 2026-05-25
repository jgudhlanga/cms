<?php

namespace App\DTO\Integrations;

use App\Http\Requests\Integrations\CreateReceiptRequest;

readonly class CreateReceiptDto
{
    public function __construct(
        public int     $tenant_id,
        public int     $fee_type_id,
        public string  $type,
        public string  $payment_status,
        public float   $amount,
        public string  $system_reference,
        public ?string $payment_reference,
    )
    {
    }


    public static function fromCreateReceiptRequest(CreateReceiptRequest $request): CreateReceiptDto
    {
        return new self(
            tenant_id: $request->tenant_id,
            fee_type_id: $request->fee_type_id,
            type: $request->type,
            payment_status: $request->payment_status,
            amount: $request->amount,
            system_reference: $request->system_reference,
            payment_reference: $request->payment_reference,
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id'         => $this->tenant_id,
            'fee_type_id'       => $this->fee_type_id,
            'type'              => $this->type,
            'payment_status'    => $this->payment_status,
            'amount'            => $this->amount,
            'system_reference'  => $this->system_reference,
            'payment_reference' => $this->payment_reference,
        ];
    }
}

