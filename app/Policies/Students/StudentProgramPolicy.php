<?php

namespace App\Policies\Students;

use App\Enums\Acl\PermissionEnum;
use App\Models\Students\StudentProgram;
use App\Models\Users\User;

class StudentProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_STUDENT_PROGRAMS) ||
            $user->can(PermissionEnum::VIEW_STUDENT_PROGRAMS) ||
            $user->can(PermissionEnum::ROOT_MANAGE) ||
            $user->can(PermissionEnum::VIEW_ONLY_OWN_DEPARTMENT);
    }

    public function view(User $user, StudentProgram $studentProgram): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_STUDENT_PROGRAMS) ||
            $user->can(PermissionEnum::VIEW_STUDENT_PROGRAMS) ||
            $user->can(PermissionEnum::ROOT_MANAGE) ||
            $user->can(PermissionEnum::VIEW_ONLY_OWN_DEPARTMENT);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::CREATE_STUDENT_PROGRAMS);
    }

    public function update(User $user, StudentProgram $studentProgram): bool
    {
        return $user->can(PermissionEnum::UPDATE_STUDENT_PROGRAMS, $studentProgram);
    }

    public function delete(User $user, StudentProgram $studentProgram): bool
    {
        return $user->can(PermissionEnum::DELETE_STUDENT_PROGRAMS, $studentProgram);
    }

    public function restore(User $user, StudentProgram $studentProgram): bool
    {
        return $user->can(PermissionEnum::RESTORE_STUDENT_PROGRAMS, $studentProgram);
    }

    public function forceDelete(User $user, StudentProgram $studentProgram): bool
    {
        return $user->can(PermissionEnum::FORCE_DELETE_STUDENT_PROGRAMS, $studentProgram);
    }
}
