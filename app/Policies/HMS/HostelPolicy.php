<?php

namespace App\Policies\HMS;

use App\Models\HMS\Hostel;
use App\Models\Users\User;

class HostelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:hostels');
    }

    public function view(User $user, Hostel $hostel): bool
    {
        return $user->can('viewAny:hostels') || $user->can('view:hostels');
    }

    public function create(User $user): bool
    {
        return $user->can('create:hostels');
    }

    public function update(User $user, Hostel $hostel): bool
    {
        return $user->can('update:hostels', $hostel);
    }

    public function delete(User $user, Hostel $hostel): bool
    {
        return $user->can('delete:hostels', $hostel);
    }

    public function restore(User $user, Hostel $hostel): bool
    {
        return $user->can('restore:hostels', $hostel);
    }

    public function forceDelete(User $user, Hostel $hostel): bool
    {
        return $user->can('forceDelete:hostels', $hostel);
    }
}
