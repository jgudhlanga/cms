<?php

namespace App\Policies\HMS;

use App\Models\HMS\HostelRoomAllocation;
use App\Models\Users\User;

class HostelRoomAllocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:hostel-room-allocations');
    }

    public function view(User $user, HostelRoomAllocation $hostelRoomAllocation): bool
    {
        return $user->can('viewAny:hostel-room-allocations') || $user->can('view:hostel-room-allocations');
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
