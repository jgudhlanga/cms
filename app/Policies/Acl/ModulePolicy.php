<?php

namespace App\Policies\Acl;

use App\Models\Acl\Module;
use App\Models\Users\User;

class ModulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:modules');
    }

    public function view(User $user, Module $module): bool
    {
        return $user->can('viewAny:modules') || $user->can('view:modules');
    }

    public function create(User $user): bool
    {
        return $user->can('create:modules');
    }

    public function update(User $user, Module $module): bool
    {
        return $user->can('update:modules', $module);
    }

    public function delete(User $user, Module $module): bool
    {
        return $user->can('delete:modules', $module);
    }

    public function restore(User $user, Module $module): bool
    {
        return $user->can('restore:modules', $module);
    }

    public function forceDelete(User $user, Module $module): bool
    {
        return $user->can('forceDelete:modules', $module);
    }
}
