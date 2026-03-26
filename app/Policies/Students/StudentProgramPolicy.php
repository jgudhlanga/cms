<?php

namespace App\Policies\Students;

use App\Models\Students\StudentProgram;
use App\Models\Users\User;

class StudentProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:student-programs') ||
            $user->can('view:student-programs') ||
            $user->can('root:manage') ||
            $user->can('viewOnlyOwnDepartment:departments');
    }

    public function view(User $user, StudentProgram $studentProgram): bool
    {
        return $user->can('viewAny:student-programs') ||
            $user->can('view:student-programs') ||
            $user->can('root:manage') ||
            $user->can('viewOnlyOwnDepartment:departments');
    }

    public function create(User $user): bool
    {
        return $user->can('create:student-programs');
    }

    public function update(User $user, StudentProgram $studentProgram): bool
    {
        return $user->can('update:student-programs', $studentProgram);
    }

    public function delete(User $user, StudentProgram $studentProgram): bool
    {
        return $user->can('delete:student-programs', $studentProgram);
    }

    public function restore(User $user, StudentProgram $studentProgram): bool
    {
        return $user->can('restore:student-programs', $studentProgram);
    }

    public function forceDelete(User $user, StudentProgram $studentProgram): bool
    {
        return $user->can('forceDelete:student-programs', $studentProgram);
    }
}
