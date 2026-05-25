<?php

namespace App\DTO\Students;

use App\Http\Requests\Students\StudentEnrolmentStatusRequest;

readonly class StudentEnrolmentStatusDto
{
    public function __construct(
        public string $name,
        public ?string $description,
        public ?string $color,
    ) {}

    public static function fromRequest(StudentEnrolmentStatusRequest $request): self
    {
        return new self(
            name: $request->name,
            description: $request->description,
            color: $request->color,
        );
    }
}
