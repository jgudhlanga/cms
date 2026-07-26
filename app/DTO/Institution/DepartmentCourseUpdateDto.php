<?php

namespace App\DTO\Institution;

use App\Http\Requests\Institution\DepartmentCourseUpdateRequest;

readonly class DepartmentCourseUpdateDto
{
    public function __construct(
        public array $department_level_ids,
        public bool $show_on_current_application_period,
        public ?bool $coursework_capture_enabled = null,
    ) {}

    public static function fromDepartmentCourseUpdateRequest(DepartmentCourseUpdateRequest $request): DepartmentCourseUpdateDto
    {
        return new self(
            department_level_ids: $request->department_level_ids ?? [],
            show_on_current_application_period: (bool) $request->show_on_current_application_period,
            coursework_capture_enabled: $request->has('coursework_capture_enabled')
                ? $request->boolean('coursework_capture_enabled')
                : null,
        );
    }
}
