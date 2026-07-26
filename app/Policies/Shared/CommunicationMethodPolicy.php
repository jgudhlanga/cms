<?php

namespace App\Policies\Shared;

use App\Models\Shared\CommunicationMethod;
use App\Models\Users\User;

class CommunicationMethodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:communication-methods');
    }

    public function view(User $user, CommunicationMethod $communicationMethod): bool
    {
        return $user->can('viewAny:communication-methods') || $user->can('view:communication-methods');
    }

    public function create(User $user): bool
    {
        return $user->can('create:communication-methods');
    }

    public function update(User $user, CommunicationMethod $communicationMethod): bool
    {
        return $user->can('update:communication-methods');
    }

    public function delete(User $user, CommunicationMethod $communicationMethod): bool
    {
        return $user->can('delete:communication-methods');
    }

    public function restore(User $user, CommunicationMethod $communicationMethod): bool
    {
        return $user->can('restore:communication-methods');
    }

    public function forceDelete(User $user, CommunicationMethod $communicationMethod): bool
    {
        return $user->can('forceDelete:communication-methods');
    }
}
