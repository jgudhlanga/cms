<?php

namespace App\Services\Lecturer;

use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Users\User;
use App\Support\Rbac\UserAccessScope;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Course work access has two levels:
 *  - view: assigned tutors/module lecturers, or academic administrators within the departments they can reach;
 *  - capture (create/update/import): the assigned module lecturer, or holders of captureForOthers:course-work
 *    within the departments they can reach.
 */
class LecturerCourseWorkAccess
{
    /** @var array<int, int|null> */
    private array $classConfigIdByClassId = [];

    /** @var array<int, int|null> */
    private array $departmentIdByClassConfigId = [];

    public function __construct(
        private readonly LecturerAssignmentResolver $assignmentResolver,
    ) {}

    public function isAcademicAdmin(User $user): bool
    {
        return $user->can('viewAny:academic-calendars');
    }

    public function canViewClassAsAdmin(User $user, int $classId): bool
    {
        return $this->isAcademicAdmin($user)
            && $this->canReachDepartment($user, $this->departmentIdForClass($classId));
    }

    public function canViewClassConfigAsAdmin(User $user, int $classConfigId): bool
    {
        return $this->isAcademicAdmin($user)
            && $this->canReachDepartment($user, $this->departmentIdForClassConfig($classConfigId));
    }

    public function canCaptureForOthersInClass(User $user, int $classId): bool
    {
        return $user->can('captureForOthers:course-work')
            && $this->canReachDepartment($user, $this->departmentIdForClass($classId));
    }

    public function canCaptureForOthersInClassConfig(User $user, int $classConfigId): bool
    {
        return $user->can('captureForOthers:course-work')
            && $this->canReachDepartment($user, $this->departmentIdForClassConfig($classConfigId));
    }

    public function canAccessClass(User $user, int $classId): bool
    {
        if ($this->canViewClassAsAdmin($user, $classId) || $this->canCaptureForOthersInClass($user, $classId)) {
            return true;
        }

        $resolved = $this->assignmentResolver->resolveForUser($user);

        return in_array($classId, $resolved['classIds'], true)
            || $this->isLecturerInChargeOfClass($resolved, $classId);
    }

    public function canAccessClassModule(User $user, int $classId, int $moduleId): bool
    {
        if ($this->canViewClassAsAdmin($user, $classId) || $this->canCaptureForOthersInClass($user, $classId)) {
            return true;
        }

        $resolved = $this->assignmentResolver->resolveForUser($user);

        return $this->assignmentResolver->isAssigned($resolved, $classId, $moduleId)
            || $this->isLecturerInChargeOfClass($resolved, $classId);
    }

    public function canCaptureClassModule(User $user, int $classId, int $moduleId): bool
    {
        $resolved = $this->assignmentResolver->resolveForUser($user);

        if ($this->assignmentResolver->isAssigned($resolved, $classId, $moduleId)) {
            return true;
        }

        return $this->canCaptureForOthersInClass($user, $classId);
    }

    public function canAccessMark(User $user, CourseWorkMark $mark): bool
    {
        $classId = $this->classIdForEnrolment((int) $mark->student_enrolment_id);

        if ($classId === null) {
            return false;
        }

        return $this->canAccessClassModule($user, $classId, (int) $mark->course_syllabus_module_id);
    }

    public function canCaptureMark(User $user, CourseWorkMark $mark): bool
    {
        $classId = $this->classIdForEnrolment((int) $mark->student_enrolment_id);

        if ($classId === null) {
            return false;
        }

        return $this->canCaptureClassModule($user, $classId, (int) $mark->course_syllabus_module_id);
    }

    public function canAccessEnrolmentModule(User $user, int $studentEnrolmentId, int $moduleId, ?int $classId = null): bool
    {
        $resolvedClassId = $classId ?? $this->classIdForEnrolment($studentEnrolmentId);

        if ($resolvedClassId === null) {
            return false;
        }

        return $this->canAccessClassModule($user, $resolvedClassId, $moduleId);
    }

    public function canCaptureEnrolmentModule(User $user, int $studentEnrolmentId, int $moduleId, ?int $classId = null): bool
    {
        $resolvedClassId = $classId ?? $this->classIdForEnrolment($studentEnrolmentId);

        if ($resolvedClassId === null) {
            return false;
        }

        return $this->canCaptureClassModule($user, $resolvedClassId, $moduleId);
    }

    public function hasAssignmentInClassConfig(User $user, int $classConfigId): bool
    {
        if (
            $this->canViewClassConfigAsAdmin($user, $classConfigId)
            || $this->canCaptureForOthersInClassConfig($user, $classConfigId)
        ) {
            return true;
        }

        $resolved = $this->assignmentResolver->resolveForUser($user);

        if (in_array($classConfigId, $resolved['lecturerInChargeClassConfigIds'] ?? [], true)) {
            return true;
        }

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
        if (
            $this->canViewClassConfigAsAdmin($user, $classConfigId)
            || $this->canCaptureForOthersInClassConfig($user, $classConfigId)
        ) {
            return true;
        }

        $resolved = $this->assignmentResolver->resolveForUser($user);

        return in_array($classConfigId, $resolved['lecturerInChargeClassConfigIds'] ?? [], true)
            || $this->isAssignedToModuleInClassConfig($user, $classConfigId, $moduleId);
    }

    public function canCaptureModuleInClassConfig(User $user, int $classConfigId, int $moduleId): bool
    {
        if ($this->isAssignedToModuleInClassConfig($user, $classConfigId, $moduleId)) {
            return true;
        }

        return $this->canCaptureForOthersInClassConfig($user, $classConfigId);
    }

    /**
     * @return list<int> Empty list means every module in the class is visible.
     */
    public function allowedModuleIdsForClass(User $user, int $classId): array
    {
        if ($this->canViewClassAsAdmin($user, $classId) || $this->canCaptureForOthersInClass($user, $classId)) {
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

    public function assertCanCaptureClassModule(User $user, int $classId, int $moduleId): void
    {
        if (! $this->canCaptureClassModule($user, $classId, $moduleId)) {
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

    public function assertCanCaptureEnrolmentModule(
        User $user,
        int $studentEnrolmentId,
        int $moduleId,
        ?int $classId = null,
    ): void {
        if (! $this->canCaptureEnrolmentModule($user, $studentEnrolmentId, $moduleId, $classId)) {
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

    public function assertCanCaptureModuleInClassConfig(User $user, int $classConfigId, int $moduleId): void
    {
        if (! $this->canCaptureModuleInClassConfig($user, $classConfigId, $moduleId)) {
            throw new AuthorizationException;
        }
    }

    /**
     * The class a student currently sits in: the live placement first, otherwise the most recent one.
     */
    public function classIdForEnrolment(int $studentEnrolmentId): ?int
    {
        $classId = AcademicCalendarStudentEnrolment::query()
            ->where('student_enrolment_id', $studentEnrolmentId)
            ->whereNull('deleted_at')
            ->orderByDesc('is_live')
            ->orderByDesc('id')
            ->value('academic_calendar_class_id');

        return $classId !== null ? (int) $classId : null;
    }

    private function isAssignedToModuleInClassConfig(User $user, int $classConfigId, int $moduleId): bool
    {
        $resolved = $this->assignmentResolver->resolveForUser($user);

        if ($resolved['classIds'] === []) {
            return false;
        }

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
     * A Lecturer in Charge may view (never capture) every class in the class configs they lead.
     *
     * @param  array<string, mixed>  $resolved
     */
    private function isLecturerInChargeOfClass(array $resolved, int $classId): bool
    {
        $classConfigIds = $resolved['lecturerInChargeClassConfigIds'] ?? [];

        if ($classConfigIds === []) {
            return false;
        }

        if (! array_key_exists($classId, $this->classConfigIdByClassId)) {
            $classConfigId = AcademicCalendarClass::query()->whereKey($classId)->value('class_config_id');
            $this->classConfigIdByClassId[$classId] = $classConfigId !== null ? (int) $classConfigId : null;
        }

        return in_array($this->classConfigIdByClassId[$classId], $classConfigIds, true);
    }

    private function canReachDepartment(User $user, ?int $institutionDepartmentId): bool
    {
        return UserAccessScope::for($user)->canReachDepartment((int) $institutionDepartmentId);
    }

    private function departmentIdForClass(int $classId): ?int
    {
        if (! array_key_exists($classId, $this->classConfigIdByClassId)) {
            $classConfigId = AcademicCalendarClass::query()->whereKey($classId)->value('class_config_id');
            $this->classConfigIdByClassId[$classId] = $classConfigId !== null ? (int) $classConfigId : null;
        }

        $classConfigId = $this->classConfigIdByClassId[$classId];

        return $classConfigId !== null ? $this->departmentIdForClassConfig($classConfigId) : null;
    }

    private function departmentIdForClassConfig(int $classConfigId): ?int
    {
        if (! array_key_exists($classConfigId, $this->departmentIdByClassConfigId)) {
            $departmentId = ClassConfig::query()->whereKey($classConfigId)->value('institution_department_id');
            $this->departmentIdByClassConfigId[$classConfigId] = $departmentId !== null ? (int) $departmentId : null;
        }

        return $this->departmentIdByClassConfigId[$classConfigId];
    }
}
