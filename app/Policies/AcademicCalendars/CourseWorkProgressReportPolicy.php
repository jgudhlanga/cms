<?php

namespace App\Policies\AcademicCalendars;

use App\Models\AcademicCalendars\CourseWorkProgressReport;
use App\Models\Users\User;
use App\Support\Rbac\UserAccessScope;

class CourseWorkProgressReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('acknowledge:course-work-progress-reports') || $user->can('view:course-work-progress');
    }

    public function acknowledge(User $user, CourseWorkProgressReport $report): bool
    {
        return $user->can('acknowledge:course-work-progress-reports')
            && (int) $report->submitted_by !== (int) $user->id
            && UserAccessScope::for($user)->canReachDepartment((int) $report->institution_department_id);
    }
}
