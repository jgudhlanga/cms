<?php

namespace App\Policies\Institution;

use App\Models\Institution\FeeStructure;
use App\Models\Users\User;

class FeeStructurePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:fee-structures');
    }

    public function view(User $user, FeeStructure $feeStructure): bool
    {
        return $user->can('viewAny:fee-structures') || $user->can('view:fee-structures');
    }

    public function create(User $user): bool
    {
        return $user->can('create:fee-structures');
    }

    public function update(User $user, FeeStructure $feeStructure): bool
    {
        return $user->can('update:fee-structures', $feeStructure);
    }

    public function delete(User $user, FeeStructure $feeStructure): bool
    {
        return $user->can('delete:fee-structures', $feeStructure);
    }

    public function restore(User $user, FeeStructure $feeStructure): bool
    {
        return $user->can('restore:fee-structures', $feeStructure);
    }

    public function forceDelete(User $user, FeeStructure $feeStructure): bool
    {
        return $user->can('forceDelete:fee-structures', $feeStructure);
    }
}
