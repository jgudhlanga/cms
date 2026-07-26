<?php

namespace App\Policies\Shared;

use App\Models\Shared\MaritalStatus;
use App\Models\Users\User;

class MaritalStatusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:marital-statuses');
    }

    public function view(User $user, MaritalStatus $maritalStatus): bool
    {
        return $user->can('viewAny:marital-statuses') || $user->can('view:marital-statuses');
    }

    public function create(User $user): bool
    {
        return $user->can('create:marital-statuses');
    }

    public function update(User $user, MaritalStatus $maritalStatus): bool
    {
        return $user->can('update:marital-statuses');
    }

    public function delete(User $user, MaritalStatus $maritalStatus): bool
    {
        return $user->can('delete:marital-statuses');
    }

    public function restore(User $user, MaritalStatus $maritalStatus): bool
    {
        return $user->can('restore:marital-statuses');
    }

    public function forceDelete(User $user, MaritalStatus $maritalStatus): bool
    {
        return $user->can('forceDelete:marital-statuses');
    }
}
