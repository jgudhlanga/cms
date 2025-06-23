<?php

namespace App\DTO\Religions;

use App\Http\Requests\Shared\ReligionRequest;

readonly class ReligionDto
{
    public function __construct(
        public string  $name,
        public ?string $description,
    )
    {
    }


    public static function fromReligionRequest(ReligionRequest $request): ReligionDto
    {
        return new self(
            name: $request->name,
            description: $request->description,
        );
    }
}
