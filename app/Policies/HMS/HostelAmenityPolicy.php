<?php

namespace App\Policies\HMS;

use App\Models\HMS\HostelAmenity;
use App\Models\Users\User;

class HostelAmenityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:hostel-amenities');
    }

    public function view(User $user, HostelAmenity $hostelAmenity): bool
    {
        return $user->can('viewAny:hostel-amenities') || $user->can('view:hostel-amenities');
    }

    public function create(User $user): bool
    {
        return $user->can('create:hostel-amenities');
    }

    public function update(User $user, HostelAmenity $hostelAmenity): bool
    {
        return $user->can('update:hostel-amenities', $hostelAmenity);
    }

    public function delete(User $user, HostelAmenity $hostelAmenity): bool
    {
        return $user->can('delete:hostel-amenities', $hostelAmenity);
    }

    public function restore(User $user, HostelAmenity $hostelAmenity): bool
    {
        return $user->can('restore:hostel-amenities', $hostelAmenity);
    }

    public function forceDelete(User $user, HostelAmenity $hostelAmenity): bool
    {
        return $user->can('forceDelete:hostel-amenities', $hostelAmenity);
    }
}
