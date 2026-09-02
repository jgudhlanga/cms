<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\Applications\ApplicationCourseRequirement;
use App\Models\Applications\ApplicationLevelRequirement;

class OLevelRequirementResolver
{
    public function resolve(?int $departmentLevelId, ?int $departmentCourseId): ApplicationLevelRequirement|ApplicationCourseRequirement|null
    {
        if ($departmentLevelId && $departmentCourseId) {
            $courseRequirement = ApplicationCourseRequirement::query()
                ->where('department_level_id', $departmentLevelId)
                ->where('department_course_id', $departmentCourseId)
                ->first();

            if ($courseRequirement?->is_o_level_required) {
                return $courseRequirement;
            }
        }

        if ($departmentLevelId) {
            $levelRequirement = ApplicationLevelRequirement::query()
                ->where('department_level_id', $departmentLevelId)
                ->first();

            if ($levelRequirement?->is_o_level_required) {
                return $levelRequirement;
            }
        }

        return null;
    }

    /**
     * Class-list ranking: a saved course requirement row always wins, even when
     * O-level is off on that row. Falls back to the department-level row.
     */
    public function resolveRanking(?int $departmentLevelId, ?int $departmentCourseId): ApplicationLevelRequirement|ApplicationCourseRequirement|null
    {
        if ($departmentLevelId && $departmentCourseId) {
            $courseRequirement = ApplicationCourseRequirement::query()
                ->where('department_level_id', $departmentLevelId)
                ->where('department_course_id', $departmentCourseId)
                ->first();

            if ($courseRequirement !== null) {
                return $courseRequirement;
            }
        }

        if ($departmentLevelId) {
            return ApplicationLevelRequirement::query()
                ->where('department_level_id', $departmentLevelId)
                ->first();
        }

        return null;
    }
}
