<?php

namespace App\DTO\Integrations;

use App\Http\Requests\Integrations\CreateInvoiceRequest;

readonly class CreateInvoiceDto
{
    public function __construct(
        public int     $tenant_id,
        public int     $fee_type_id,
        public string  $type,
        public string  $payment_status,
        public float   $amount,
        public string  $system_reference,
        public ?string $payment_reference,
        public ?string $response_code,
        public ?string $response_message,
    )
    {
    }


    public static function fromCreateInvoiceRequest(CreateInvoiceRequest $request): CreateInvoiceDto
    {
        return new self(
            tenant_id: $request->tenant_id,
            fee_type_id: $request->fee_type_id,
            type: $request->type,
            payment_status: $request->payment_status,
            amount: $request->amount,
            system_reference: $request->system_reference,
            payment_reference: $request->payment_reference,
            response_code: $request->response_code,
            response_message: $request->response_message,
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
            'response_code'     => $this->response_code,
            'response_message'  => $this->response_message,
        ];
    }
}
