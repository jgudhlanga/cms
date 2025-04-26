<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\CourseRequest;

readonly class CourseDto
{
    public function __construct(
        public string  $name,
        public ?string $description,
    )
    {
    }


    public static function fromCourseRequest(CourseRequest $request): CourseDto
    {
        return new self(
            name: $request->name,
            description: $request->description,
        );
    }
}
