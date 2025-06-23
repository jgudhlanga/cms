<?php

namespace App\DTO\Payments;

use App\Http\Requests\Shared\PaymentMethodRequest;

class PaymentMethodDto
{
    public function __construct(
        public readonly string $title,
		public readonly? string $description,
    )
    {
    }


    public static function fromPaymentMethodRequest(PaymentMethodRequest $request): PaymentMethodDto
    {
        return new self(
            title: $request->title,
			description: $request->description,
        );
    }
}
