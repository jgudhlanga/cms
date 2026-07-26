<?php

namespace App\Policies\Shared;

use App\Models\Shared\Country;
use App\Models\Users\User;

class CountryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:countries');
    }

    public function view(User $user, Country $country): bool
    {
        return $user->can('viewAny:countries') || $user->can('view:countries');
    }

    public function create(User $user): bool
    {
        return $user->can('create:countries');
    }

    public function update(User $user, Country $country): bool
    {
        return $user->can('update:countries');
    }

    public function delete(User $user, Country $country): bool
    {
        return $user->can('delete:countries');
    }

    public function restore(User $user, Country $country): bool
    {
        return $user->can('restore:countries');
    }

    public function forceDelete(User $user, Country $country): bool
    {
        return $user->can('forceDelete:countries');
    }
}
