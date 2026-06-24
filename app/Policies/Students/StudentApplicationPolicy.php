<?php

namespace App\Policies\Students;

use App\Models\Students\StudentApplication;
use App\Models\Users\User;

class StudentApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:student-applications') ||
            $user->can('view:student-applications') ||
            $user->can('root:manage') ||
            $user->can('viewOnlyOwnDepartment:departments');
    }

    public function view(User $user, StudentApplication $studentApplication): bool
    {
        return $user->can('viewAny:student-applications') ||
            $user->can('view:student-applications') ||
            $user->can('root:manage') ||
            $user->can('viewOnlyOwnDepartment:departments');
    }

    public function create(User $user): bool
    {
        return $user->can('create:student-applications');
    }

    public function update(User $user, StudentApplication $studentApplication): bool
    {
        return $user->can('update:student-applications', $studentApplication);
    }

    public function delete(User $user, StudentApplication $studentApplication): bool
    {
        return $user->can('delete:student-applications', $studentApplication);
    }

    public function restore(User $user, StudentApplication $studentApplication): bool
    {
        return $user->can('restore:student-applications', $studentApplication);
    }

    public function forceDelete(User $user, StudentApplication $studentApplication): bool
    {
        return $user->can('forceDelete:student-applications', $studentApplication);
    }
}
