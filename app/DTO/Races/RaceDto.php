<?php

namespace App\DTO\Races;

use App\Http\Requests\Shared\RaceRequest;

class RaceDto
{
    public function __construct(
        public readonly string $title,
        public readonly? string $description,
    )
    {
    }


    public static function fromRaceRequest(RaceRequest $request): RaceDto
    {
        return new self(
            title: $request->title,
			description: $request->description,
        );
    }
}
