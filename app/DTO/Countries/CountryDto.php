<?php

namespace App\DTO\Countries;

use App\Http\Requests\Countries\CountryRequest;

readonly class CountryDto
{
    public function __construct(
        public string  $name,
        public ?string $flag,
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
