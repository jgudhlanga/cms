<?php

namespace App\Policies\Shared;

use App\Models\Shared\IdType;
use App\Models\Users\User;

class IdTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:id-types');
    }

    public function view(User $user, IdType $idType): bool
    {
        return $user->can('viewAny:id-types') || $user->can('view:id-types');
    }

    public function create(User $user): bool
    {
        return $user->can('create:id-types');
    }

    public function update(User $user, IdType $idType): bool
    {
        return $user->can('update:id-types');
    }

    public function delete(User $user, IdType $idType): bool
    {
        return $user->can('delete:id-types');
    }

    public function restore(User $user, IdType $idType): bool
    {
        return $user->can('restore:id-types');
    }

    public function forceDelete(User $user, IdType $idType): bool
    {
        return $user->can('forceDelete:id-types');
    }
}
