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
        if (! ($user->can('viewAny:hostels') || $user->can('view:hostels'))) {
            return false;
        }

        return $this->canAccessHostel($user, $hostel);
    }

    public function create(User $user): bool
    {
        return $user->can('create:hostels');
    }

    public function update(User $user, Hostel $hostel): bool
    {
        if (! $user->can('update:hostels')) {
            return false;
        }

        return $this->canAccessHostel($user, $hostel);
    }

    public function delete(User $user, Hostel $hostel): bool
    {
        if (! $user->can('delete:hostels')) {
            return false;
        }

        return $this->canAccessHostel($user, $hostel);
    }

    public function restore(User $user, Hostel $hostel): bool
    {
        if (! $user->can('restore:hostels')) {
            return false;
        }

        return $this->canAccessHostel($user, $hostel);
    }

    public function forceDelete(User $user, Hostel $hostel): bool
    {
        if (! $user->can('forceDelete:hostels')) {
            return false;
        }

        return $this->canAccessHostel($user, $hostel);
    }

    private function canAccessHostel(User $user, Hostel $hostel): bool
    {
        if (! $user->can('viewOnlyOwnHostel:hostels')) {
            return true;
        }

        $staffId = $user->staffProfile?->id;

        if ($staffId === null) {
            return false;
        }

        return (int) $hostel->warden_id === (int) $staffId;
    }
}
