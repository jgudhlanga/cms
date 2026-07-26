<?php

namespace App\Policies\Shared;

use App\Models\Shared\AddressType;
use App\Models\Users\User;

class AddressTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:address-types');
    }

    public function view(User $user, AddressType $addressType): bool
    {
        return $user->can('viewAny:address-types') || $user->can('view:address-types');
    }

    public function create(User $user): bool
    {
        return $user->can('create:address-types');
    }

    public function update(User $user, AddressType $addressType): bool
    {
        return $user->can('update:address-types');
    }

    public function delete(User $user, AddressType $addressType): bool
    {
        return $user->can('delete:address-types');
    }

    public function restore(User $user, AddressType $addressType): bool
    {
        return $user->can('restore:address-types');
    }

    public function forceDelete(User $user, AddressType $addressType): bool
    {
        return $user->can('forceDelete:address-types');
    }
}
