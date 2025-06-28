<?php

namespace App\DTO\Shared;

use App\Http\Requests\Shared\AddressTypeRequest;

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
