<?php

namespace App\Policies\AcademicCalendars;

use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Users\User;

class CourseWorkPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:course-work');
    }

    public function view(User $user, ?CourseWorkMark $courseWorkMark = null): bool
    {
        return $user->can('viewAny:course-work') || $user->can('view:course-work');
    }

    public function create(User $user): bool
    {
        return $user->can('create:course-work');
    }

    public function update(User $user, ?CourseWorkMark $courseWorkMark = null): bool
    {
        return $user->can('update:course-work');
    }

    public function delete(User $user, ?CourseWorkMark $courseWorkMark = null): bool
    {
        return $user->can('delete:course-work');
    }

    public function restore(User $user, ?CourseWorkMark $courseWorkMark = null): bool
    {
        return $user->can('restore:course-work');
    }

    public function forceDelete(User $user, ?CourseWorkMark $courseWorkMark = null): bool
    {
        return $user->can('forceDelete:course-work');
    }

    public function viewAuditTrail(User $user): bool
    {
        return $user->can('viewAuditTrail:course-work');
    }

    public function export(User $user): bool
    {
        return $user->can('export:course-work');
    }

    public function import(User $user): bool
    {
        return $user->can('import:course-work');
    }
}
