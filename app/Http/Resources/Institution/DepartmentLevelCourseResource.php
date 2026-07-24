<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentLevelCourseResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $this->resource->loadMissing([
            'departmentLevel.level',
            'departmentCourse.course',
        ]);

        return [
            'id' => $this->id,
            'departmentCourseId' => $this?->department_course_id,
            'departmentLevelId' => $this?->department_level_id,
            'level' => $this?->departmentLevel?->level?->name,
            'course' => $this?->departmentCourse?->course?->name,
            'hasEnrolmentRequirements' => $this->departmentCourse?->course?->has_enrolment_requirements,
        ];
    }
}
