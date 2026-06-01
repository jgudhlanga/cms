<?php

namespace App\Policies\HMS;

use App\Models\HMS\HostelRoomAllocation;
use App\Models\Users\User;
use App\Support\HMS\HmsStudentAccess;

class HostelRoomAllocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:hostel-room-allocations')
            || HmsStudentAccess::canManageOwnAccommodation($user);
    }

    public function view(User $user, HostelRoomAllocation $hostelRoomAllocation): bool
    {
        return HmsStudentAccess::canViewAllocation($user, $hostelRoomAllocation);
    }

    public function create(User $user): bool
    {
        return $user->can('create:hostel-room-allocations');
    }

    public function update(User $user, HostelRoomAllocation $hostelRoomAllocation): bool
    {
        return $user->can('update:hostel-room-allocations', $hostelRoomAllocation);
    }

    public function delete(User $user, HostelRoomAllocation $hostelRoomAllocation): bool
    {
        return $user->can('delete:hostel-room-allocations', $hostelRoomAllocation);
    }

    public function restore(User $user, HostelRoomAllocation $hostelRoomAllocation): bool
    {
        return $user->can('restore:hostel-room-allocations', $hostelRoomAllocation);
    }

    public function forceDelete(User $user, HostelRoomAllocation $hostelRoomAllocation): bool
    {
        return $user->can('forceDelete:hostel-room-allocations', $hostelRoomAllocation);
    }
}
