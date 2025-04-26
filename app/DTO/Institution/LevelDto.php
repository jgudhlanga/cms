<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\LevelRequest;

readonly class LevelDto
{
    public function __construct(
        public string  $name,
        public ?string $description,
    )
    {
    }


    public static function fromLevelRequest(LevelRequest $request): LevelDto
    {
        return new self(
            name: $request->name,
            description: $request->description,
        );
    }
}
