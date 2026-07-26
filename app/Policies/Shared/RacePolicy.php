<?php

namespace App\Policies\Shared;

use App\Models\Shared\Race;
use App\Models\Users\User;

class RacePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:races');
    }

    public function view(User $user, Race $race): bool
    {
        return $user->can('viewAny:races') || $user->can('view:races');
    }

    public function create(User $user): bool
    {
        return $user->can('create:races');
    }

    public function update(User $user, Race $race): bool
    {
        return $user->can('update:races');
    }

    public function delete(User $user, Race $race): bool
    {
        return $user->can('delete:races');
    }

    public function restore(User $user, Race $race): bool
    {
        return $user->can('restore:races');
    }

    public function forceDelete(User $user, Race $race): bool
    {
        return $user->can('forceDelete:races');
    }
}
