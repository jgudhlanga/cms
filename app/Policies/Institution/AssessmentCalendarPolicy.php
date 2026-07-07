<?php

namespace App\Policies\Institution;

use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Users\User;

class AssessmentCalendarPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:assessment-calendar');
    }

    public function view(User $user, ?AssessmentCalendar $assessmentCalendar = null): bool
    {
        return $user->can('viewAny:assessment-calendar') || $user->can('view:assessment-calendar');
    }

    public function create(User $user): bool
    {
        return $user->can('create:assessment-calendar');
    }

    public function update(User $user, ?AssessmentCalendar $assessmentCalendar = null): bool
    {
        return $user->can('update:assessment-calendar');
    }

    public function delete(User $user, ?AssessmentCalendar $assessmentCalendar = null): bool
    {
        return $user->can('delete:assessment-calendar');
    }

    public function restore(User $user, ?AssessmentCalendar $assessmentCalendar = null): bool
    {
        return $user->can('restore:assessment-calendar');
    }

    public function forceDelete(User $user, ?AssessmentCalendar $assessmentCalendar = null): bool
    {
        return $user->can('forceDelete:assessment-calendar');
    }

    public function viewAuditTrail(User $user): bool
    {
        return $user->can('viewAuditTrail:assessment-calendar');
    }

    public function export(User $user): bool
    {
        return $user->can('export:assessment-calendar');
    }

    public function import(User $user): bool
    {
        return $user->can('import:assessment-calendar');
    }
}
