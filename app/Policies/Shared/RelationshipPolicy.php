<?php

namespace App\Policies\Shared;

use App\Models\Shared\Relationship;
use App\Models\Users\User;

class RelationshipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:relationships');
    }

    public function view(User $user, Relationship $relationship): bool
    {
        return $user->can('viewAny:relationships') || $user->can('view:relationships');
    }

    public function create(User $user): bool
    {
        return $user->can('create:relationships');
    }

    public function update(User $user, Relationship $relationship): bool
    {
        return $user->can('update:relationships');
    }

    public function delete(User $user, Relationship $relationship): bool
    {
        return $user->can('delete:relationships');
    }

    public function restore(User $user, Relationship $relationship): bool
    {
        return $user->can('restore:relationships');
    }

    public function forceDelete(User $user, Relationship $relationship): bool
    {
        return $user->can('forceDelete:relationships');
    }
}
