<?php

namespace App\Policies\Shared;

use App\Models\Shared\FeeType;
use App\Models\Users\User;

class FeeTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:fee-types');
    }

    public function view(User $user, FeeType $feeType): bool
    {
        return $user->can('viewAny:fee-types') || $user->can('view:fee-types');
    }

    public function create(User $user): bool
    {
        return $user->can('create:fee-types');
    }

    public function update(User $user, FeeType $feeType): bool
    {
        return $user->can('update:fee-types');
    }

    public function delete(User $user, FeeType $feeType): bool
    {
        return $user->can('delete:fee-types');
    }

    public function restore(User $user, FeeType $feeType): bool
    {
        return $user->can('restore:fee-types');
    }

    public function forceDelete(User $user, FeeType $feeType): bool
    {
        return $user->can('forceDelete:fee-types');
    }
}
