<?php

namespace App\DTO\AcademicCalendars;

use App\Http\Requests\AcademicCalendars\AcademicYearOptionRequest;

readonly class AcademicYearOptionDto
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {}

    public static function fromRequest(AcademicYearOptionRequest $request): self
    {
        return new self(
            name: $request->name,
            description: $request->description,
        );
    }
}
