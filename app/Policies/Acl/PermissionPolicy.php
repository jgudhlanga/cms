<?php

namespace App\Policies\Acl;

use App\Enums\PermissionEnum;
use App\Models\Acl\Permission;
use App\Models\Users\User;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_PERMISSIONS);
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_PERMISSIONS) || $user->can(PermissionEnum::VIEW_PERMISSION);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::CREATE_PERMISSION);
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->can(PermissionEnum::UPDATE_PERMISSION, $permission);
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->can(PermissionEnum::DELETE_PERMISSION, $permission);
    }

    public function restore(User $user, Permission $permission): bool
    {
        return $user->can(PermissionEnum::RESTORE_PERMISSION, $permission);
    }

    public function forceDelete(User $user, Permission $permission): bool
    {
		return $user->can(PermissionEnum::FORCE_DELETE_PERMISSION, $permission);
    }
}
