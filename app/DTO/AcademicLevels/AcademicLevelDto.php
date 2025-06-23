<?php

namespace App\DTO\AcademicLevels;

use App\Http\Requests\Academiclevels\AcademicLevelRequest;

readonly class AcademicLevelDto
{
    public function __construct(
        public string  $name,
        public ?int $position,
        public ?string $description,
    )
    {
    }


    public static function fromAcademicLevelRequest(AcademicLevelRequest $request): AcademicLevelDto
    {
        return new self(
            name: $request->name,
            position: $request->position,
            description: $request->description,
        );
    }
}
