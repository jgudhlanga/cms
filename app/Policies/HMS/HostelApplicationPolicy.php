<?php

namespace App\Policies\HMS;

use App\Models\HMS\HostelApplication;
use App\Models\Students\Student;
use App\Models\Users\User;
use App\Support\HMS\HmsStudentAccess;

class HostelApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:hostel-applications')
            || HmsStudentAccess::canManageOwnAccommodation($user);
    }

    public function view(User $user, HostelApplication $hostelApplication): bool
    {
        return HmsStudentAccess::canViewApplication($user, $hostelApplication);
    }

    public function create(User $user): bool
    {
        return $user->can('create:hostel-applications')
            || HmsStudentAccess::canManageOwnAccommodation($user);
    }

    public function createForStudent(User $user, Student $student): bool
    {
        return HmsStudentAccess::canCreateApplicationFor($user, $student);
    }

    public function update(User $user, HostelApplication $hostelApplication): bool
    {
        if (HmsStudentAccess::canManageOwnAccommodation($user)
            && ! HmsStudentAccess::isStaffHmsUser($user)) {
            return false;
        }

        return $user->can('update:hostel-applications', $hostelApplication);
    }

    public function delete(User $user, HostelApplication $hostelApplication): bool
    {
        if (HmsStudentAccess::canManageOwnAccommodation($user)
            && ! HmsStudentAccess::isStaffHmsUser($user)) {
            return false;
        }

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
