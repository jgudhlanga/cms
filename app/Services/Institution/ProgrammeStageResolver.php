<?php

declare(strict_types=1);

namespace App\Services\Institution;

use App\Actions\Institution\SyncProgrammeSemestersForOfferingAction;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\ProgrammeStage;

class ProgrammeStageResolver
{
    public function __construct(
        protected SyncProgrammeSemestersForOfferingAction $syncProgrammeSemesters,
    ) {}

    public function offering(int $departmentCourseId, int $departmentLevelId): ?DepartmentLevelCourse
    {
        return DepartmentLevelCourse::query()
            ->where('department_course_id', $departmentCourseId)
            ->where('department_level_id', $departmentLevelId)
            ->with(['programmeStages', 'programmeSemesters', 'departmentLevel.level'])
            ->first();
    }

    public function firstStage(int $departmentCourseId, int $departmentLevelId): ?ProgrammeStage
    {
        $offering = $this->offeringOrSync($departmentCourseId, $departmentLevelId);

        if ($offering === null) {
            return null;
        }

        $first = $offering->programmeStages->sortBy('stage_number')->first();

        return $first instanceof ProgrammeStage ? $first : null;
    }

    public function nextStage(ProgrammeStage $stage): ?ProgrammeStage
    {
        $next = ProgrammeStage::query()
            ->where('department_level_course_id', $stage->department_level_course_id)
            ->where('stage_number', '>', $stage->stage_number)
            ->orderBy('stage_number')
            ->first();

        return $next instanceof ProgrammeStage ? $next : null;
    }

    public function offeringOrSync(int $departmentCourseId, int $departmentLevelId): ?DepartmentLevelCourse
    {
        $offering = $this->offering($departmentCourseId, $departmentLevelId);

        if ($offering === null) {
            return null;
        }

        if ($offering->programmeStages->isEmpty() || $offering->programmeSemesters->isEmpty()) {
            $this->syncProgrammeSemesters->execute($offering);
            $offering->load(['programmeStages', 'programmeSemesters', 'departmentLevel.level']);
        }

        return $offering;
    }
}
