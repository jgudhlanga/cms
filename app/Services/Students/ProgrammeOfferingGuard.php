<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\DTO\Students\ReassignStudentProgrammeDto;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use Illuminate\Validation\ValidationException;

class ProgrammeOfferingGuard
{
    public function assert(ReassignStudentProgrammeDto $target): void
    {
        $departmentLevel = DepartmentLevel::query()->find($target->departmentLevelId);
        $departmentCourse = DepartmentCourse::query()->find($target->departmentCourseId);

        if (! $departmentLevel instanceof DepartmentLevel || ! $departmentCourse instanceof DepartmentCourse) {
            throw ValidationException::withMessages([
                'department_course_id' => [__('students.reassign_programme_invalid_offering')],
            ]);
        }

        if ((int) $departmentLevel->institution_department_id !== $target->institutionDepartmentId
            || (int) $departmentCourse->institution_department_id !== $target->institutionDepartmentId) {
            throw ValidationException::withMessages([
                'institution_department_id' => [__('students.reassign_programme_department_mismatch')],
            ]);
        }

        $linked = DepartmentLevelCourse::query()
            ->where('department_course_id', $target->departmentCourseId)
            ->where('department_level_id', $target->departmentLevelId)
            ->exists();

        if (! $linked) {
            throw ValidationException::withMessages([
                'department_course_id' => [__('students.reassign_programme_level_course_unlinked')],
            ]);
        }
    }
}
