<?php

namespace App\DTO\AddressTypes;

use App\Http\Requests\AddressTypes\AddressTypeRequest;

class AddressTypeDto
{
    public function __construct(
        public readonly string $title,
        public readonly? string $description,
    )
    {
    }


    public static function fromAddressTypeRequest(AddressTypeRequest $request): AddressTypeDto
    {
        return new self(
            title: $request->title,
			description: $request->description,
        );
    }
}
