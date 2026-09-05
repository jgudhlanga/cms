<?php

declare(strict_types=1);

namespace App\DTO\Students;

use App\Models\Students\StudentApplication;

readonly class ReassignStudentProgrammeDto
{
    public function __construct(
        public int $institutionDepartmentId,
        public int $departmentLevelId,
        public int $departmentCourseId,
        public int $modeOfStudyId,
    ) {}

    /**
     * @param  array{institution_department_id: int|string, department_level_id: int|string, department_course_id: int|string, mode_of_study_id: int|string}  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            institutionDepartmentId: (int) $payload['institution_department_id'],
            departmentLevelId: (int) $payload['department_level_id'],
            departmentCourseId: (int) $payload['department_course_id'],
            modeOfStudyId: (int) $payload['mode_of_study_id'],
        );
    }

    public function matchesApplication(StudentApplication $application): bool
    {
        return (int) $application->institution_department_id === $this->institutionDepartmentId
            && (int) $application->department_level_id === $this->departmentLevelId
            && (int) $application->department_course_id === $this->departmentCourseId
            && (int) $application->mode_of_study_id === $this->modeOfStudyId;
    }
}
