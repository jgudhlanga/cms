<?php

namespace App\DTO\Shared;

use App\Http\Requests\Shared\PaymentDayRequest;

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
