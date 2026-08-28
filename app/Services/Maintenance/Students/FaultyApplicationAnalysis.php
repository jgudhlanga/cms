<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Students;

use App\Models\Students\StudentApplication;

class FaultyApplicationAnalysis
{
    public const REASON_MISSING_LEVEL = 'missing_level';

    public const REASON_MISSING_DEPARTMENT = 'missing_department';

    public const REASON_MISSING_COURSE = 'missing_course';

    public const REASON_MISSING_MODE_OF_STUDY = 'missing_mode_of_study';

    public const REASON_MISSING_INTAKE = 'missing_intake';

    /**
     * @return list<string>
     */
    public function reasons(StudentApplication $application): array
    {
        $application->loadMissing([
            'departmentLevel.level',
            'institutionDepartment',
            'departmentCourse',
            'modeOfStudy',
            'intakePeriod',
        ]);

        $reasons = [];

        if ($this->isMissingLevel($application)) {
            $reasons[] = self::REASON_MISSING_LEVEL;
        }

        if ($application->institution_department_id === null || $application->institutionDepartment === null) {
            $reasons[] = self::REASON_MISSING_DEPARTMENT;
        }

        if ($application->department_course_id === null || $application->departmentCourse === null) {
            $reasons[] = self::REASON_MISSING_COURSE;
        }

        if ($application->mode_of_study_id === null || $application->modeOfStudy === null) {
            $reasons[] = self::REASON_MISSING_MODE_OF_STUDY;
        }

        if ($application->intake_period_id === null || $application->intakePeriod === null) {
            $reasons[] = self::REASON_MISSING_INTAKE;
        }

        return $reasons;
    }

    /**
     * Mirrors the missing-level rule used on the student profile application cards.
     */
    public function isMissingLevel(StudentApplication $application): bool
    {
        if ($application->department_level_id === null || $application->departmentLevel === null) {
            return true;
        }

        return trim((string) $application->departmentLevel->level?->name) === '';
    }
}
