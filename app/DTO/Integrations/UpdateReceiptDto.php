<?php

namespace App\DTO\Integrations;

use App\Http\Requests\INtegrations\UpdateReceiptRequest;

readonly class UpdateReceiptDto
{
    public function __construct(
        public string  $payment_status,
        public string  $payment_option,
        public string  $payment_date,
        public float   $amount,
        public string  $system_reference,
        public ?string $payment_reference,
        public string  $currency,
        public float   $client_fee,
        public float   $merchant_fee,
    )
    {
    }


    public static function fromUpdateReceiptRequest(UpdateReceiptRequest $request): UpdateReceiptDto
    {
        return new self(
            payment_status: $request->payment_status,
            payment_option: $request->payment_option,
            payment_date: $request->payment_date,
            amount: $request->amount,
            system_reference: $request->system_reference,
            payment_reference: $request->payment_reference,
            currency: $request->currency,
            client_fee: $request->client_fee,
            merchant_fee: $request->merchant_fee,
        );
    }
}

