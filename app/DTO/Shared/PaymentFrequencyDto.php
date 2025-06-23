<?php

namespace App\DTO\Shared;

use App\Http\Requests\Shared\PaymentFrequencyRequest;

class PaymentFrequencyDto
{
    public function __construct(
        public readonly string $title,
		public readonly? string $description,
    )
    {
    }


    public static function fromPaymentFrequencyRequest(PaymentFrequencyRequest $request): PaymentFrequencyDto
    {
        return new self(
            title: $request->title,
			description: $request->description,
        );
    }
}
