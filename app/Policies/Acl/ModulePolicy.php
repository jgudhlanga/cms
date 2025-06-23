<?php

namespace App\Policies\Acl;

use App\Enums\Shared\PermissionEnum;
use App\Models\Acl\Module;
use App\Models\Users\User;

class ModulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_MODULES);
    }

    public function view(User $user, Module $module): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_MODULES) || $user->can(PermissionEnum::VIEW_MODULE);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::CREATE_MODULE);
    }

    public function update(User $user, Module $module): bool
    {
        return $user->can(PermissionEnum::UPDATE_MODULE, $module);
    }

    public function delete(User $user, Module $module): bool
    {
        return $user->can(PermissionEnum::DELETE_MODULE, $module);
    }

    public function restore(User $user, Module $module): bool
    {
        return $user->can(PermissionEnum::RESTORE_MODULE, $module);
    }

    public function forceDelete(User $user, Module $module): bool
    {
		return $user->can(PermissionEnum::FORCE_DELETE_MODULE, $module);
    }
}
