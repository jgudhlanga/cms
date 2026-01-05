<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\DepartmentCourseUpdateRequest;

readonly class DepartmentCourseUpdateDto
{
    public function __construct(
        public array $department_level_ids,
        public array $course_mode_ids,
        public bool  $show_on_current_application_period,
    )
    {
    }

    public static function fromDepartmentCourseUpdateRequest(DepartmentCourseUpdateRequest $request): DepartmentCourseUpdateDto
    {
        return new self(
            department_level_ids: $request->department_level_ids,
            course_mode_ids: $request->course_mode_ids,
            show_on_current_application_period: $request->show_on_current_application_period,
        );
    }
}
