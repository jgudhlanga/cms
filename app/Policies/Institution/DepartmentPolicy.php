<?php

namespace App\Policies\Institution;

use App\Models\Institution\Department;
use App\Models\Users\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:departments');
    }

    public function view(User $user, Department $department): bool
    {
        return $user->can('viewAny:departments') || $user->can('view:departments');
    }

    public function create(User $user): bool
    {
        return $user->can('create:departments');
    }

    public function update(User $user, Department $department): bool
    {
        return $user->can('update:departments');
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->can('delete:departments');
    }

    public function restore(User $user, Department $department): bool
    {
        return $user->can('restore:departments');
    }

    public function forceDelete(User $user, Department $department): bool
    {
        return $user->can('forceDelete:departments');
    }
}
