<?php

namespace App\Policies\Shared;

use App\Models\Shared\NextOfKin;
use App\Models\Users\User;

class NextOfKinPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:next-of-kins');
    }

    public function view(User $user, NextOfKin $nextOfKin): bool
    {
        return $user->can('viewAny:next-of-kins') || $user->can('view:next-of-kins');
    }

    public function create(User $user): bool
    {
        return $user->can('create:next-of-kins');
    }

    public function update(User $user, NextOfKin $nextOfKin): bool
    {
        return $user->can('update:next-of-kins', $nextOfKin);
    }

    public function delete(User $user, NextOfKin $nextOfKin): bool
    {
        return $user->can('delete:next-of-kins', $nextOfKin);
    }

    public function restore(User $user, NextOfKin $nextOfKin): bool
    {
        return $user->can('restore:next-of-kins', $nextOfKin);
    }

    public function forceDelete(User $user, NextOfKin $nextOfKin): bool
    {
        return $user->can('forceDelete:next-of-kins', $nextOfKin);
    }
}
