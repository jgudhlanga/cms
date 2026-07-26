<?php

namespace App\Policies\Shared;

use App\Models\Shared\Status;
use App\Models\Users\User;

class StatusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:statuses');
    }

    public function view(User $user, Status $status): bool
    {
        return $user->can('viewAny:statuses') || $user->can('view:statuses');
    }

    public function create(User $user): bool
    {
        return $user->can('create:statuses');
    }

    public function update(User $user, Status $status): bool
    {
        return $user->can('update:statuses');
    }

    public function delete(User $user, Status $status): bool
    {
        return $user->can('delete:statuses');
    }

    public function restore(User $user, Status $status): bool
    {
        return $user->can('restore:statuses');
    }

    public function forceDelete(User $user, Status $status): bool
    {
        return $user->can('forceDelete:statuses');
    }
}
