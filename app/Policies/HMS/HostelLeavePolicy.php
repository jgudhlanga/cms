<?php

namespace App\Policies\HMS;

use App\Models\HMS\HostelLeave;
use App\Models\Students\Student;
use App\Models\Users\User;
use App\Support\HMS\HmsStudentAccess;

class HostelLeavePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:hostel-leaves')
            || HmsStudentAccess::canManageOwnAccommodation($user);
    }

    public function view(User $user, HostelLeave $hostelLeave): bool
    {
        if (HmsStudentAccess::canManageOwnAccommodation($user)
            && HmsStudentAccess::ownsStudent($user, $hostelLeave->student)) {
            return true;
        }

        return $user->can('viewAny:hostel-leaves') || $user->can('view:hostel-leaves');
    }

    public function create(User $user): bool
    {
        return $user->can('create:hostel-leaves')
            || HmsStudentAccess::canManageOwnAccommodation($user);
    }

    public function createForStudent(User $user, Student $student): bool
    {
        if ($user->can('create:hostel-leaves')) {
            return true;
        }

        return HmsStudentAccess::canManageOwnAccommodation($user)
            && HmsStudentAccess::ownsStudent($user, $student)
            && HmsStudentAccess::hasActiveHostelAllocation($student);
    }

    public function update(User $user, HostelLeave $hostelLeave): bool
    {
        if (HmsStudentAccess::canManageOwnAccommodation($user)
            && ! HmsStudentAccess::isStaffHmsUser($user)
            && HmsStudentAccess::ownsStudent($user, $hostelLeave->student)) {
            return $hostelLeave->status?->value === 'pending';
        }

        return $user->can('update:hostel-leaves', $hostelLeave);
    }

    public function delete(User $user, HostelLeave $hostelLeave): bool
    {
        return $user->can('delete:hostel-leaves', $hostelLeave);
    }

    public function restore(User $user, HostelLeave $hostelLeave): bool
    {
        return $user->can('restore:hostel-leaves', $hostelLeave);
    }

    public function forceDelete(User $user, HostelLeave $hostelLeave): bool
    {
        return $user->can('forceDelete:hostel-leaves', $hostelLeave);
    }
}
