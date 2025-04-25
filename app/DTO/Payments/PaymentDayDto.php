<?php

namespace App\DTO\Payments;

use App\Http\Requests\Payments\PaymentDayRequest;

class PaymentDayDto
{
    public function __construct(

        public readonly string $title,
        public readonly? string $description,
    )
    {
    }


    public static function fromPaymentDayRequest(PaymentDayRequest $request): PaymentDayDto
    {
        return new self(
            title: $request->title,
            description: $request->description,
        );
    }
}
