<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\CourseRequest;

readonly class CourseDto
{
    public function __construct(
        public string  $name,
        public ?string $description,
        public ?bool $has_enrolment_requirements,
    )
    {
    }


    public static function fromCourseRequest(CourseRequest $request): CourseDto
    {
        return new self(
            name: $request->name,
            description: $request->description,
            has_enrolment_requirements: $request->has_enrolment_requirements,
        );
    }
}
