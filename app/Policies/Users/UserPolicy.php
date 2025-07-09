<?php

namespace App\Policies\Users;

use App\Enums\Acl\PermissionEnum;
use App\Models\Users\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_USERS);
    }

    public function view(User $user, User $model): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_USERS)
            || $user->can(PermissionEnum::VIEW_USERS);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::CREATE_USERS);
    }

    public function update(User $user, User $model): bool
    {
        return $user->can(PermissionEnum::UPDATE_USERS, $model);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can(PermissionEnum::DELETE_USERS, $model);
    }

    public function restore(User $user, User $model): bool
    {
        return $user->can(PermissionEnum::RESTORE_USERS, $model);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->can(PermissionEnum::FORCE_DELETE_USERS, $model);
    }
}
