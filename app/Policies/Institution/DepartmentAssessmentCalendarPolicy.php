<?php

namespace App\Policies\Institution;

use App\Models\Institution\AssessmentCalendar\DepartmentAssessmentCalendar;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Users\User;
use App\Support\Rbac\UserAccessScope;

/**
 * Department assessment calendars are managed per department: the permission alone is not enough,
 * the user must also reach the department (HODs their own, heads of division their division's).
 */
class DepartmentAssessmentCalendarPolicy
{
    public function viewAny(User $user, ?InstitutionDepartment $department = null): bool
    {
        return ($user->can('viewAny:department-assessment-calendar') || $user->can('view:department-assessment-calendar'))
            && $this->reachesDepartment($user, $department?->id);
    }

    public function view(User $user, DepartmentAssessmentCalendar $calendar): bool
    {
        return ($user->can('viewAny:department-assessment-calendar') || $user->can('view:department-assessment-calendar'))
            && $this->reachesDepartment($user, $calendar->institution_department_id);
    }

    public function create(User $user, ?InstitutionDepartment $department = null): bool
    {
        return $user->can('create:department-assessment-calendar')
            && $department instanceof InstitutionDepartment
            && $this->reachesDepartment($user, $department->id);
    }

    public function update(User $user, DepartmentAssessmentCalendar $calendar): bool
    {
        return $user->can('update:department-assessment-calendar')
            && $this->reachesDepartment($user, $calendar->institution_department_id);
    }

    public function delete(User $user, DepartmentAssessmentCalendar $calendar): bool
    {
        return $user->can('delete:department-assessment-calendar')
            && $this->reachesDepartment($user, $calendar->institution_department_id);
    }

    public function restore(User $user, DepartmentAssessmentCalendar $calendar): bool
    {
        return $user->can('restore:department-assessment-calendar')
            && $this->reachesDepartment($user, $calendar->institution_department_id);
    }

    public function forceDelete(User $user, DepartmentAssessmentCalendar $calendar): bool
    {
        return $user->can('forceDelete:department-assessment-calendar')
            && $this->reachesDepartment($user, $calendar->institution_department_id);
    }

    public function viewAuditTrail(User $user, DepartmentAssessmentCalendar $calendar): bool
    {
        return $user->can('viewAuditTrail:department-assessment-calendar')
            && $this->reachesDepartment($user, $calendar->institution_department_id);
    }

    private function reachesDepartment(User $user, mixed $institutionDepartmentId): bool
    {
        return $institutionDepartmentId !== null
            && UserAccessScope::for($user)->canReachDepartment((int) $institutionDepartmentId);
    }
}
