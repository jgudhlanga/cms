<?php

namespace App\Policies\AcademicCalendars;

use App\Models\AcademicCalendars\Semester;
use App\Models\Users\User;

class SemesterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:semesters');
    }

    public function view(User $user, Semester $semester): bool
    {
        return $user->can('viewAny:semesters') || $user->can('view:semesters');
    }

    public function create(User $user): bool
    {
        return $user->can('create:semesters');
    }

    public function update(User $user, Semester $semester): bool
    {
        return $user->can('update:semesters');
    }

    public function delete(User $user, Semester $semester): bool
    {
        return $user->can('delete:semesters');
    }

    public function restore(User $user, Semester $semester): bool
    {
        return $user->can('restore:semesters');
    }

    public function forceDelete(User $user, Semester $semester): bool
    {
        return $user->can('forceDelete:semesters');
    }
}
