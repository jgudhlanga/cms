<?php

namespace App\DTO\AcademicCalendars;

use App\Http\Requests\AcademicCalendars\SemesterRequest;

readonly class SemesterDto
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {}

    public static function fromRequest(SemesterRequest $request): self
    {
        return new self(
            name: $request->name,
            description: $request->description,
        );
    }
}
