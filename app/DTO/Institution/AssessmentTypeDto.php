<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\AssessmentTypeRequest;

readonly class AssessmentTypeDto
{
    public function __construct(
        public string $name,
        public array $modes_of_study,
        public ?string $description,
    ) {}

    public static function fromAssessmentTypeRequest(AssessmentTypeRequest $request): self
    {
        return new self(
            name: $request->name,
            modes_of_study: $request->modes_of_study,
            description: $request->description,
        );
    }
}
