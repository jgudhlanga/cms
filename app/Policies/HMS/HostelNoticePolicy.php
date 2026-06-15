<?php

namespace App\Policies\HMS;

use App\Models\HMS\HostelNotice;
use App\Models\Users\User;
use App\Support\HMS\HmsStudentAccess;

class HostelNoticePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:hostel-notices')
            || HmsStudentAccess::canManageOwnAccommodation($user);
    }

    public function view(User $user, HostelNotice $hostelNotice): bool
    {
        return $user->can('viewAny:hostel-notices') || $user->can('view:hostel-notices');
    }

    public function create(User $user): bool
    {
        return $user->can('create:hostel-notices');
    }

    public function update(User $user, HostelNotice $hostelNotice): bool
    {
        return $user->can('update:hostel-notices', $hostelNotice);
    }

    public function delete(User $user, HostelNotice $hostelNotice): bool
    {
        return $user->can('delete:hostel-notices', $hostelNotice);
    }

    public function restore(User $user, HostelNotice $hostelNotice): bool
    {
        return $user->can('restore:hostel-notices', $hostelNotice);
    }

    public function forceDelete(User $user, HostelNotice $hostelNotice): bool
    {
        return $user->can('forceDelete:hostel-notices', $hostelNotice);
    }
}
