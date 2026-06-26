<?php

namespace App\Services\Students;

use App\Models\Institution\CourseRequirement;
use App\Models\Institution\DepartmentLevelRequirement;

class OLevelRequirementResolver
{
    public function resolve(?int $departmentLevelId, ?int $departmentCourseId): DepartmentLevelRequirement|CourseRequirement|null
    {
        if ($departmentLevelId && $departmentCourseId) {
            $courseRequirement = CourseRequirement::query()
                ->where('department_level_id', $departmentLevelId)
                ->where('department_course_id', $departmentCourseId)
                ->first();

            if ($courseRequirement?->is_o_level_required) {
                return $courseRequirement;
            }
        }

        if ($departmentLevelId) {
            $levelRequirement = DepartmentLevelRequirement::query()
                ->where('department_level_id', $departmentLevelId)
                ->first();

            if ($levelRequirement?->is_o_level_required) {
                return $levelRequirement;
            }
        }

        return null;
    }
}
