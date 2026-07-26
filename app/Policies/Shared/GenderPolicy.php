<?php

namespace App\Policies\Shared;

use App\Models\Shared\Gender;
use App\Models\Users\User;

class GenderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:genders');
    }

    public function view(User $user, Gender $gender): bool
    {
        return $user->can('viewAny:genders') || $user->can('view:genders');
    }

    public function create(User $user): bool
    {
        return $user->can('create:genders');
    }

    public function update(User $user, Gender $gender): bool
    {
        return $user->can('update:genders');
    }

    public function delete(User $user, Gender $gender): bool
    {
        return $user->can('delete:genders');
    }

    public function restore(User $user, Gender $gender): bool
    {
        return $user->can('restore:genders');
    }

    public function forceDelete(User $user, Gender $gender): bool
    {
        return $user->can('forceDelete:genders');
    }
}
