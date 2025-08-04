<?php

namespace App\Policies\Students;

use App\Enums\Acl\PermissionEnum;
use App\Models\Students\Student;
use App\Models\Users\User;

class StudentProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_STUDENT_PROGRAMS);
    }

    public function view(User $user, Student $student): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_STUDENT_PROGRAMS) || $user->can(PermissionEnum::VIEW_STUDENT_PROGRAMS);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::CREATE_STUDENT_PROGRAMS);
    }

    public function update(User $user, Student $student): bool
    {
        return $user->can(PermissionEnum::UPDATE_STUDENT_PROGRAMS, $student);
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->can(PermissionEnum::DELETE_STUDENT_PROGRAMS, $student);
    }

    public function restore(User $user, Student $student): bool
    {
        return $user->can(PermissionEnum::RESTORE_STUDENT_PROGRAMS, $student);
    }

    public function forceDelete(User $user, Student $student): bool
    {
        return $user->can(PermissionEnum::FORCE_DELETE_STUDENT_PROGRAMS, $student);
    }
}
