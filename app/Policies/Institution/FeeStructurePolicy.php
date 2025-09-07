<?php

namespace App\Policies\Institution;

use App\Enums\Acl\PermissionEnum;
use App\Models\Institution\FeeStructure;
use App\Models\Users\User;

class FeeStructurePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_FEE_STRUCTURES);
    }

    public function view(User $user, FeeStructure $feeStructure): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_FEE_STRUCTURES) || $user->can(PermissionEnum::VIEW_FEE_STRUCTURES);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::CREATE_FEE_STRUCTURES);
    }

    public function update(User $user, FeeStructure $feeStructure): bool
    {
        return $user->can(PermissionEnum::UPDATE_FEE_STRUCTURES, $feeStructure);
    }

    public function delete(User $user, FeeStructure $feeStructure): bool
    {
        return $user->can(PermissionEnum::DELETE_FEE_STRUCTURES, $feeStructure);
    }

    public function restore(User $user, FeeStructure $feeStructure): bool
    {
        return $user->can(PermissionEnum::RESTORE_FEE_STRUCTURES, $feeStructure);
    }

    public function forceDelete(User $user, FeeStructure $feeStructure): bool
    {
        return $user->can(PermissionEnum::FORCE_DELETE_FEE_STRUCTURES, $feeStructure);
    }
}
