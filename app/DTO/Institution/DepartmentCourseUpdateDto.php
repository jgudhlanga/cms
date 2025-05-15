<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\DepartmentCourseUpdateRequest;

readonly class DepartmentCourseUpdateDto
{
    public function __construct(
        public array $department_leve_ids,
        public bool  $show_on_current_application_period,
        public int   $course_duration,
    )
    {
    }

    public static function fromDepartmentCourseUpdateRequest(DepartmentCourseUpdateRequest $request): DepartmentCourseUpdateDto
    {
        return new self(
            department_leve_ids: $request->department_leve_ids,
            show_on_current_application_period: $request->show_on_current_application_period,
            course_duration: $request->course_duration,
        );
    }
}
