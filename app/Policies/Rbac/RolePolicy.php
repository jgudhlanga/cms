<?php

namespace App\Policies\Rbac;

use App\Enums\Rbac\RoleEnum;
use App\Models\Rbac\Permission;
use App\Models\Rbac\Role;
use App\Models\Users\User;
use App\Services\Rbac\UserPermissionMapService;

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

    /**
     * Only super users may change the super-user role or add/remove root:manage on any role.
     *
     * @param  array<int, int>  $permissionIds
     */
    public function syncPermissions(User $user, Role $role, array $permissionIds): bool
    {
        if (! $this->update($user, $role)) {
            return false;
        }

        if (app(UserPermissionMapService::class)->isSuperUser($user)) {
            return true;
        }

        if ($role->name === RoleEnum::SUPER_USER->name()) {
            return false;
        }

        $rootManageId = Permission::query()->where('name', 'root:manage')->value('id');

        if ($rootManageId === null) {
            return true;
        }

        $grantsRootManage = in_array((int) $rootManageId, $permissionIds, true);
        $roleHasRootManage = $role->permissions()->whereKey($rootManageId)->exists();

        return $grantsRootManage === $roleHasRootManage;
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
