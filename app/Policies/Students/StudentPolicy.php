<?php

namespace App\Policies\Students;

use App\Enums\Acl\PermissionEnum;
use App\Models\Students\Student;
use App\Models\Users\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_ENROLMENTS);
    }

    public function view(User $user, Student $student): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_ENROLMENTS) || $user->can(PermissionEnum::VIEW_ENROLMENTS);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::CREATE_ENROLMENTS);
    }

    public function update(User $user, Student $student): bool
    {
        return $user->can(PermissionEnum::UPDATE_ENROLMENTS, $student);
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->can(PermissionEnum::DELETE_ENROLMENTS, $student);
    }

    public function restore(User $user, Student $student): bool
    {
        return $user->can(PermissionEnum::RESTORE_ENROLMENTS, $student);
    }

    public function forceDelete(User $user, Student $student): bool
    {
        return $user->can(PermissionEnum::FORCE_DELETE_ENROLMENTS, $student);
    }
}
