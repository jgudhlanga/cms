<?php

namespace App\Policies\Rbac;

use App\Models\Rbac\Role;
use App\Models\Users\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:roles');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('viewAny:roles') || $user->can('view:roles');
    }

    public function create(User $user): bool
    {
        return $user->can('create:roles');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('update:roles', $role);
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('delete:roles', $role);
    }

    public function restore(User $user, Role $role): bool
    {
        return $user->can('restore:roles', $role);
    }

    public function forceDelete(User $user, Role $role): bool
    {
        return $user->can('forceDelete:roles', $role);
    }
}
