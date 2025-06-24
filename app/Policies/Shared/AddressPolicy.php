<?php

namespace App\Policies\Shared;

use App\Enums\Shared\PermissionEnum;
use App\Models\Shared\Address;
use App\Models\Users\User;

class AddressPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_ADDRESSES);
    }

    public function view(User $user, Address $address): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_ADDRESSES)
            || $user->can(PermissionEnum::VIEW_ADDRESSES);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::CREATE_ADDRESSES);
    }

    public function update(User $user, Address $address): bool
    {
        return $user->can(PermissionEnum::UPDATE_ADDRESSES, $address) || $user->can(PermissionEnum::MANAGE_OWN_STUDENT_CONTACT_DETAILS);
    }

    public function delete(User $user, Address $address): bool
    {
        return $user->can(PermissionEnum::DELETE_ADDRESSES, $address) || $user->can(PermissionEnum::MANAGE_OWN_STUDENT_CONTACT_DETAILS);
    }

    public function restore(User $user, Address $address): bool
    {
        return $user->can(PermissionEnum::RESTORE_ADDRESSES, $address) || $user->can(PermissionEnum::MANAGE_OWN_STUDENT_CONTACT_DETAILS);
    }

    public function forceDelete(User $user, Address $address): bool
    {
        return $user->can(PermissionEnum::FORCE_DELETE_ADDRESSES, $address) || $user->can(PermissionEnum::MANAGE_OWN_STUDENT_CONTACT_DETAILS);
    }
}
