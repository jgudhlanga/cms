<?php

namespace App\DTO\Shared;

use App\Http\Requests\Shared\FeeTypeRequest;

readonly class FeeTypeDto
{
    public function __construct(
        public string  $name,
        public ?string $description,
    )
    {
    }


    public static function fromFeeTypeRequest(FeeTypeRequest $request): FeeTypeDto
    {
        return new self(
            name: $request->name,
            description: $request->description,
        );
    }
}
