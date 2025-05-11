<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\DepartmentCourseRequest;

readonly class DepartmentCourseDto
{
    public function __construct(
        public array $course_ids,
    )
    {
    }


    public static function fromDepartmentCourseRequest(DepartmentCourseRequest $request): DepartmentCourseDto
    {
        return new self(
            course_ids: $request->course_ids,
        );
    }
}
