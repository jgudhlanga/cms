<?php

namespace App\DTO\Genders;

use App\Http\Requests\Genders\GenderRequest;

class GenderDto
{
    public function __construct(
        public readonly string $title,
        public readonly? string $description,
    )
    {
    }


    public static function fromGenderRequest(GenderRequest $request): GenderDto
    {
        return new self(
            title: $request->title,
            description: $request->description,
        );
    }
}
