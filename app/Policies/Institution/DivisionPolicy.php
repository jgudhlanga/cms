<?php

namespace App\Policies\Institution;

use App\Models\Institution\Division;
use App\Models\Users\User;

class DivisionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:divisions');
    }

    public function view(User $user, Division $division): bool
    {
        return $user->can('viewAny:divisions') || $user->can('view:divisions');
    }

    public function create(User $user): bool
    {
        return $user->can('create:divisions');
    }

    public function update(User $user, Division $division): bool
    {
        return $user->can('update:divisions');
    }

    public function delete(User $user, Division $division): bool
    {
        return $user->can('delete:divisions');
    }

    public function restore(User $user, Division $division): bool
    {
        return $user->can('restore:divisions');
    }

    public function forceDelete(User $user, Division $division): bool
    {
        return $user->can('forceDelete:divisions');
    }
}
