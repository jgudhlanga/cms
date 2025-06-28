<?php

namespace App\Policies\Shared;

use App\Enums\Shared\PermissionEnum;
use App\Models\Shared\Contact;
use App\Models\Users\User;

class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_CONTACTS);
    }

    public function view(User $user, Contact $contact): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_CONTACTS)
            || $user->can(PermissionEnum::VIEW_CONTACTS);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::CREATE_CONTACTS);
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->can(PermissionEnum::UPDATE_CONTACTS, $contact) || $user->can(PermissionEnum::MANAGE_OWN_STUDENT_CONTACT_DETAILS);
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->can(PermissionEnum::DELETE_CONTACTS, $contact) || $user->can(PermissionEnum::MANAGE_OWN_STUDENT_CONTACT_DETAILS);
    }

    public function restore(User $user, Contact $contact): bool
    {
        return $user->can(PermissionEnum::RESTORE_CONTACTS, $contact) || $user->can(PermissionEnum::MANAGE_OWN_STUDENT_CONTACT_DETAILS);
    }

    public function forceDelete(User $user, Contact $contact): bool
    {
        return $user->can(PermissionEnum::FORCE_DELETE_CONTACTS, $contact) || $user->can(PermissionEnum::MANAGE_OWN_STUDENT_CONTACT_DETAILS);
    }
}
