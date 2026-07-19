<?php

namespace App\Services\Lecturer;

use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Users\User;
use Illuminate\Auth\Access\AuthorizationException;

class LecturerCourseWorkAccess
{
    public function __construct(
        private readonly LecturerAssignmentResolver $assignmentResolver,
    ) {}

    public function isAcademicAdmin(User $user): bool
    {
        return $user->can('viewAny:academic-calendars');
    }

    public function canAccessClass(User $user, int $classId): bool
    {
        if ($this->isAcademicAdmin($user)) {
            return true;
        }

        $resolved = $this->assignmentResolver->resolveForUser($user);

        return in_array($classId, $resolved['classIds'], true);
    }

    public function canAccessClassModule(User $user, int $classId, int $moduleId): bool
    {
        if ($this->isAcademicAdmin($user)) {
            return true;
        }

        $resolved = $this->assignmentResolver->resolveForUser($user);

        return $this->assignmentResolver->isAssigned($resolved, $classId, $moduleId);
    }

    public function canAccessMark(User $user, CourseWorkMark $mark): bool
    {
        if ($this->isAcademicAdmin($user)) {
            return true;
        }

        $classId = $this->classIdForEnrolment((int) $mark->student_enrolment_id);

        if ($classId === null) {
            return false;
        }

        return $this->canAccessClassModule($user, $classId, (int) $mark->course_syllabus_module_id);
    }

    public function canAccessEnrolmentModule(User $user, int $studentEnrolmentId, int $moduleId, ?int $classId = null): bool
    {
        if ($this->isAcademicAdmin($user)) {
            return true;
        }

        $resolvedClassId = $classId ?? $this->classIdForEnrolment($studentEnrolmentId);

        if ($resolvedClassId === null) {
            return false;
        }

        return $this->canAccessClassModule($user, $resolvedClassId, $moduleId);
    }

    public function hasAssignmentInClassConfig(User $user, int $classConfigId): bool
    {
        if ($this->isAcademicAdmin($user)) {
            return true;
        }

        $resolved = $this->assignmentResolver->resolveForUser($user);

        if ($resolved['classIds'] === []) {
            return false;
        }

        return AcademicCalendarClass::query()
            ->where('class_config_id', $classConfigId)
            ->whereIn('id', $resolved['classIds'])
            ->exists();
    }

    public function canAccessModuleInClassConfig(User $user, int $classConfigId, int $moduleId): bool
    {
        if ($this->isAcademicAdmin($user)) {
            return true;
        }

        $resolved = $this->assignmentResolver->resolveForUser($user);

        $classIds = AcademicCalendarClass::query()
            ->where('class_config_id', $classConfigId)
            ->whereIn('id', $resolved['classIds'])
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        foreach ($classIds as $classId) {
            if ($this->assignmentResolver->isAssigned($resolved, $classId, $moduleId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<int>
     */
    public function allowedModuleIdsForClass(User $user, int $classId): array
    {
        if ($this->isAcademicAdmin($user)) {
            return [];
        }

        $resolved = $this->assignmentResolver->resolveForUser($user);
        $moduleIds = [];

        foreach ($resolved['assignmentKeys'] as $key) {
            [$assignedClassId, $moduleId] = array_map('intval', explode('-', $key, 2));

            if ($assignedClassId === $classId) {
                $moduleIds[] = $moduleId;
            }
        }

        return array_values(array_unique($moduleIds));
    }

    public function assertCanAccessClass(User $user, int $classId): void
    {
        if (! $this->canAccessClass($user, $classId)) {
            throw new AuthorizationException;
        }
    }

    public function assertCanAccessClassModule(User $user, int $classId, int $moduleId): void
    {
        if (! $this->canAccessClassModule($user, $classId, $moduleId)) {
            throw new AuthorizationException;
        }
    }

    public function assertCanAccessEnrolmentModule(
        User $user,
        int $studentEnrolmentId,
        int $moduleId,
        ?int $classId = null,
    ): void {
        if (! $this->canAccessEnrolmentModule($user, $studentEnrolmentId, $moduleId, $classId)) {
            throw new AuthorizationException;
        }
    }

    public function assertCanAccessClassConfig(User $user, int $classConfigId): void
    {
        if (! $this->hasAssignmentInClassConfig($user, $classConfigId)) {
            throw new AuthorizationException;
        }
    }

    public function assertCanAccessModuleInClassConfig(User $user, int $classConfigId, int $moduleId): void
    {
        if (! $this->canAccessModuleInClassConfig($user, $classConfigId, $moduleId)) {
            throw new AuthorizationException;
        }
    }

    private function classIdForEnrolment(int $studentEnrolmentId): ?int
    {
        $classId = AcademicCalendarStudentEnrolment::query()
            ->where('student_enrolment_id', $studentEnrolmentId)
            ->whereNull('deleted_at')
            ->value('academic_calendar_class_id');

        return $classId !== null ? (int) $classId : null;
    }
}
