<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\ModeOfStudyRequest;

readonly class ModeOfStudyDto
{
    public function __construct(
        public string  $name,
        public ?string $description,
    )
    {
    }

    public static function fromModeOfStudyRequest(ModeOfStudyRequest $request): ModeOfStudyDto
    {
        return new self(
            name: $request->name,
            description: $request->description,
        );
    }
}
