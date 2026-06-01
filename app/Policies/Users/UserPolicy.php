<?php

namespace App\Policies\Users;

use App\Models\Users\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:users');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('viewAny:users') || $user->can('view:users');
    }

    public function create(User $user): bool
    {
        return $user->can('create:users');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('update:users', $model);
    }

    public function updateCredentials(User $user, User $model): bool
    {
        if ($user->can('update:users', $model)) {
            return true;
        }

        return $user->id === $model->id
            && $user->can('manageOwnStudentPersonalDetails:students');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('delete:users', $model);
    }

    public function restore(User $user, User $model): bool
    {
        return $user->can('restore:users', $model);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->can('forceDelete:users', $model);
    }
}
