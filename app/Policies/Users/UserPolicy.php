<?php

namespace App\Policies\Users;

use App\Enums\Rbac\RoleEnum;
use App\Models\Rbac\Role;
use App\Models\Users\User;
use App\Services\Rbac\UserPermissionMapService;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:users');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('viewAny:users') || $user->can('view:users');
    }

    public function create(User $user): bool
    {
        return $user->can('create:users');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('update:users', $model) && $this->canManageAccount($user, $model);
    }

    public function updateCredentials(User $user, User $model): bool
    {
        if ($user->can('update:users', $model) && $this->canManageAccount($user, $model)) {
            return true;
        }

        return $user->id === $model->id
            && $user->can('manageOwnStudentPersonalDetails:students');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('delete:users', $model) && $this->canManageAccount($user, $model);
    }

    public function restore(User $user, User $model): bool
    {
        return $user->can('restore:users', $model) && $this->canManageAccount($user, $model);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->can('forceDelete:users', $model) && $this->canManageAccount($user, $model);
    }

    /**
     * Only super users may grant or remove the super-user role or any role carrying root:manage.
     *
     * @param  array<int, int|string>  $roleIds
     */
    public function assignRoles(User $user, ?User $target, array $roleIds): bool
    {
        if (app(UserPermissionMapService::class)->isSuperUser($user)) {
            return true;
        }

        if ($target !== null && ! $this->canManageAccount($user, $target)) {
            return false;
        }

        $requestedRoleIds = collect($roleIds)->map(fn ($id): int => (int) $id)->unique()->values();

        // Repositories only sync roles when role ids are supplied, so an empty list changes nothing.
        if ($requestedRoleIds->isEmpty()) {
            return true;
        }

        $currentRoleIds = $target === null
            ? collect()
            : $target->roles()->pluck('roles.id')->map(fn ($id): int => (int) $id);

        $changedRoleIds = $requestedRoleIds->diff($currentRoleIds)
            ->merge($currentRoleIds->diff($requestedRoleIds))
            ->unique();

        if ($changedRoleIds->isEmpty()) {
            return true;
        }

        return ! Role::query()
            ->whereKey($changedRoleIds->all())
            ->where(fn ($query) => $query
                ->where('name', RoleEnum::SUPER_USER->name())
                ->orWhereHas('permissions', fn ($permissions) => $permissions->where('name', 'root:manage')))
            ->exists();
    }

    /**
     * Super users can only be managed by super users; root-level accounts only by root-level staff.
     */
    private function canManageAccount(User $user, User $model): bool
    {
        $permissionMap = app(UserPermissionMapService::class);

        if ($permissionMap->isSuperUser($user)) {
            return true;
        }

        if ($permissionMap->isSuperUser($model)) {
            return false;
        }

        return ! $model->can('root:manage') || $user->can('root:manage');
    }
}
