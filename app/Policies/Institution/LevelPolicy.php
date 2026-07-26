<?php

namespace App\Policies\Institution;

use App\Models\Institution\Level;
use App\Models\Users\User;

class LevelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:levels');
    }

    public function view(User $user, Level $level): bool
    {
        return $user->can('viewAny:levels') || $user->can('view:levels');
    }

    public function create(User $user): bool
    {
        return $user->can('create:levels');
    }

    public function update(User $user, Level $level): bool
    {
        return $user->can('update:levels');
    }

    public function delete(User $user, Level $level): bool
    {
        return $user->can('delete:levels');
    }

    public function restore(User $user, Level $level): bool
    {
        return $user->can('restore:levels');
    }

    public function forceDelete(User $user, Level $level): bool
    {
        return $user->can('forceDelete:levels');
    }
}
