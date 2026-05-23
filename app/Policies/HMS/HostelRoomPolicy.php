<?php

namespace App\Policies\HMS;

use App\Models\HMS\HostelRoom;
use App\Models\Users\User;

class HostelRoomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view:hostel-rooms');
    }

    public function view(User $user, HostelRoom $hostelRoom): bool
    {
        return $user->can('view:hostel-rooms');
    }

    public function create(User $user): bool
    {
        return $user->can('create:hostel-rooms');
    }

    public function update(User $user, HostelRoom $hostelRoom): bool
    {
        return $user->can('update:hostel-rooms', $hostelRoom);
    }

    public function delete(User $user, HostelRoom $hostelRoom): bool
    {
        return $user->can('delete:hostel-rooms', $hostelRoom);
    }

    public function restore(User $user, HostelRoom $hostelRoom): bool
    {
        return $user->can('restore:hostel-rooms', $hostelRoom);
    }

    public function forceDelete(User $user, HostelRoom $hostelRoom): bool
    {
        return $user->can('forceDelete:hostel-rooms', $hostelRoom);
    }
}
