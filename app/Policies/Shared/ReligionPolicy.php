<?php

namespace App\Policies\Shared;

use App\Models\Shared\Religion;
use App\Models\Users\User;

class ReligionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:religions');
    }

    public function view(User $user, Religion $religion): bool
    {
        return $user->can('viewAny:religions') || $user->can('view:religions');
    }

    public function create(User $user): bool
    {
        return $user->can('create:religions');
    }

    public function update(User $user, Religion $religion): bool
    {
        return $user->can('update:religions');
    }

    public function delete(User $user, Religion $religion): bool
    {
        return $user->can('delete:religions');
    }

    public function restore(User $user, Religion $religion): bool
    {
        return $user->can('restore:religions');
    }

    public function forceDelete(User $user, Religion $religion): bool
    {
        return $user->can('forceDelete:religions');
    }
}
