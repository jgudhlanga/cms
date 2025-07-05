<?php

namespace App\DTO\Shared;

use App\Http\Requests\Shared\IdTypeRequest;

readonly class IdTypeDto
{
    public function __construct(
        public string  $name,
        public ?string $description,
    )
    {
    }


    public static function fromIdTypeRequest(IdTypeRequest $request): IdTypeDto
    {
        return new self(
            name: $request->name,
            description: $request->description,
        );
    }
}
