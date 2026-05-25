<?php

namespace App\Policies\Shared;

use App\Models\Shared\Address;
use App\Models\Users\User;

class AddressPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:addresses');
    }

    public function view(User $user, Address $address): bool
    {
        return $user->can('viewAny:addresses') || $user->can('view:addresses');
    }

    public function create(User $user): bool
    {
        return $user->can('create:addresses');
    }

    public function update(User $user, Address $address): bool
    {
        return $user->can('update:addresses', $address) || $user->can('manageOwnStudentContactDetails:students');
    }

    public function delete(User $user, Address $address): bool
    {
        return $user->can('delete:addresses', $address) || $user->can('manageOwnStudentContactDetails:students');
    }

    public function restore(User $user, Address $address): bool
    {
        return $user->can('restore:addresses', $address) || $user->can('manageOwnStudentContactDetails:students');
    }

    public function forceDelete(User $user, Address $address): bool
    {
        return $user->can('forceDelete:addresses', $address) || $user->can('manageOwnStudentContactDetails:students');
    }
}
