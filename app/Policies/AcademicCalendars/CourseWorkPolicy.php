<?php

namespace App\Policies\AcademicCalendars;

use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Users\User;
use App\Services\Lecturer\LecturerCourseWorkAccess;

class CourseWorkPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:course-work');
    }

    public function view(User $user, ?CourseWorkMark $courseWorkMark = null): bool
    {
        if (! ($user->can('viewAny:course-work') || $user->can('view:course-work'))) {
            return false;
        }

        if ($courseWorkMark === null) {
            return true;
        }

        return app(LecturerCourseWorkAccess::class)->canAccessMark($user, $courseWorkMark);
    }

    public function create(User $user): bool
    {
        return $user->can('create:course-work');
    }

    public function update(User $user, ?CourseWorkMark $courseWorkMark = null): bool
    {
        if (! $user->can('update:course-work')) {
            return false;
        }

        if ($courseWorkMark === null) {
            return true;
        }

        return app(LecturerCourseWorkAccess::class)->canAccessMark($user, $courseWorkMark);
    }

    public function delete(User $user, ?CourseWorkMark $courseWorkMark = null): bool
    {
        if (! $user->can('delete:course-work')) {
            return false;
        }

        if ($courseWorkMark === null) {
            return true;
        }

        return app(LecturerCourseWorkAccess::class)->canAccessMark($user, $courseWorkMark);
    }

    public function restore(User $user, ?CourseWorkMark $courseWorkMark = null): bool
    {
        if (! $user->can('restore:course-work')) {
            return false;
        }

        if ($courseWorkMark === null) {
            return true;
        }

        return app(LecturerCourseWorkAccess::class)->canAccessMark($user, $courseWorkMark);
    }

    public function forceDelete(User $user, ?CourseWorkMark $courseWorkMark = null): bool
    {
        if (! $user->can('forceDelete:course-work')) {
            return false;
        }

        if ($courseWorkMark === null) {
            return true;
        }

        return app(LecturerCourseWorkAccess::class)->canAccessMark($user, $courseWorkMark);
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
