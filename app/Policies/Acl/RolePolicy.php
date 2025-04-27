<?php

namespace App\Policies\Acl;

use App\Enums\PermissionEnum;
use App\Models\Acl\Role;
use App\Models\Users\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_ROLES);
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_ROLES) || $user->can(PermissionEnum::VIEW_ROLE);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::CREATE_ROLE);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can(PermissionEnum::UPDATE_ROLE, $role);
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can(PermissionEnum::DELETE_ROLE, $role);
    }

    public function restore(User $user, Role $role): bool
    {
        return $user->can(PermissionEnum::RESTORE_ROLE, $role);
    }

    public function forceDelete(User $user, Role $role): bool
    {
		return $user->can(PermissionEnum::FORCE_DELETE_ROLE, $role);
    }
}
