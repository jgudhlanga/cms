<?php

namespace App\Policies\Shared;

use App\Models\Shared\Province;
use App\Models\Users\User;

class ProvincePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:provinces');
    }

    public function view(User $user, Province $province): bool
    {
        return $user->can('viewAny:provinces') || $user->can('view:provinces');
    }

    public function create(User $user): bool
    {
        return $user->can('create:provinces');
    }

    public function update(User $user, Province $province): bool
    {
        return $user->can('update:provinces');
    }

    public function delete(User $user, Province $province): bool
    {
        return $user->can('delete:provinces');
    }

    public function restore(User $user, Province $province): bool
    {
        return $user->can('restore:provinces');
    }

    public function forceDelete(User $user, Province $province): bool
    {
        return $user->can('forceDelete:provinces');
    }
}
