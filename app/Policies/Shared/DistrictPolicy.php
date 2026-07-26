<?php

namespace App\Policies\Shared;

use App\Models\Shared\District;
use App\Models\Users\User;

class DistrictPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:districts');
    }

    public function view(User $user, District $district): bool
    {
        return $user->can('viewAny:districts') || $user->can('view:districts');
    }

    public function create(User $user): bool
    {
        return $user->can('create:districts');
    }

    public function update(User $user, District $district): bool
    {
        return $user->can('update:districts');
    }

    public function delete(User $user, District $district): bool
    {
        return $user->can('delete:districts');
    }

    public function restore(User $user, District $district): bool
    {
        return $user->can('restore:districts');
    }

    public function forceDelete(User $user, District $district): bool
    {
        return $user->can('forceDelete:districts');
    }
}
