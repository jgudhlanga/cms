<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\DivisionRequest;

readonly class DivisionDto
{
    public function __construct(
        public string  $name,
        public ?string $description,
    )
    {
    }


    public static function fromDivisionRequest(DivisionRequest $request): DivisionDto
    {
        return new self(
            name: $request->name,
            description: $request->description,
        );
    }
}
