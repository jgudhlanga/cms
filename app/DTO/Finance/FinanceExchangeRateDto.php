<?php

namespace App\DTO\Finance;

use App\Http\Requests\Finance\FinanceExchangeRateRequest;

class FinanceExchangeRateDto
{
    public function __construct(
        public readonly string $date,
        public readonly string $currency_from,
        public readonly string $currency_to,
        public readonly string $rate,
    ) {}

    public static function fromFinanceExchangeRateRequest(FinanceExchangeRateRequest $request): self
    {
        return new self(
            date: (string) $request->date,
            currency_from: (string) $request->currency_from,
            currency_to: (string) $request->currency_to,
            rate: (string) $request->rate,
        );
    }
}
