<?php

namespace App\Policies\Shared;

use App\Models\Shared\Contact;
use App\Models\Users\User;

class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:contacts');
    }

    public function view(User $user, Contact $contact): bool
    {
        return $user->can('viewAny:contacts') || $user->can('view:contacts');
    }

    public function create(User $user): bool
    {
        return $user->can('create:contacts');
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->can('update:contacts', $contact) || $user->can('manageOwnStudentContactDetails:students');
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->can('delete:contacts', $contact) || $user->can('manageOwnStudentContactDetails:students');
    }

    public function restore(User $user, Contact $contact): bool
    {
        return $user->can('restore:contacts', $contact) || $user->can('manageOwnStudentContactDetails:students');
    }

    public function forceDelete(User $user, Contact $contact): bool
    {
        return $user->can('forceDelete:contacts', $contact) || $user->can('manageOwnStudentContactDetails:students');
    }
}
