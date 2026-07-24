<?php

namespace App\Policies\Rbac;

use App\Models\Rbac\Permission;
use App\Models\Users\User;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:permissions');
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->can('viewAny:permissions') || $user->can('view:permissions');
    }

    public function create(User $user): bool
    {
        return $user->can('create:permissions');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->can('update:permissions', $permission);
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->can('delete:permissions', $permission);
    }

    public function restore(User $user, Permission $permission): bool
    {
        return $user->can('restore:permissions', $permission);
    }

    public function forceDelete(User $user, Permission $permission): bool
    {
        return $user->can('forceDelete:permissions', $permission);
    }
}
