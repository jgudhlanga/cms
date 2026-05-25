<?php

namespace App\Policies\HMS;

use App\Models\HMS\HostelApplication;
use App\Models\Users\User;

class HostelApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:hostel-applications');
    }

    public function view(User $user, HostelApplication $hostelApplication): bool
    {
        return $user->can('viewAny:hostel-applications') || $user->can('view:hostel-applications');
    }

    public function create(User $user): bool
    {
        return $user->can('create:hostel-applications');
    }

    public function update(User $user, HostelApplication $hostelApplication): bool
    {
        return $user->can('update:hostel-applications', $hostelApplication);
    }

    public function delete(User $user, HostelApplication $hostelApplication): bool
    {
        return $user->can('delete:hostel-applications', $hostelApplication);
    }

    public function restore(User $user, HostelApplication $hostelApplication): bool
    {
        return $user->can('restore:hostel-applications', $hostelApplication);
    }

    public function forceDelete(User $user, HostelApplication $hostelApplication): bool
    {
        return $user->can('forceDelete:hostel-applications', $hostelApplication);
    }
}
