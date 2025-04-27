<?php

namespace App\DTO\Countries;

use App\Http\Requests\Countries\CountryRequest;

class CountryDto
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $flag,
    )
    {
    }


    public static function fromCountryRequest(CountryRequest $request): CountryDto
    {
        return new self(
            name: $request->name,
            flag: $request->flag,
        );
    }
}
