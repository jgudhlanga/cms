<?php

namespace App\Policies\HMS;

use App\Models\HMS\HostelQuery;
use App\Models\Students\Student;
use App\Models\Users\User;
use App\Support\HMS\HmsStudentAccess;

class HostelQueryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:hostel-queries')
            || HmsStudentAccess::canManageOwnAccommodation($user);
    }

    public function view(User $user, HostelQuery $hostelQuery): bool
    {
        if (HmsStudentAccess::canManageOwnAccommodation($user)
            && HmsStudentAccess::ownsStudent($user, $hostelQuery->student)) {
            return true;
        }

        return $user->can('viewAny:hostel-queries') || $user->can('view:hostel-queries');
    }

    public function create(User $user): bool
    {
        return $user->can('create:hostel-queries')
            || HmsStudentAccess::canManageOwnAccommodation($user);
    }

    public function createForStudent(User $user, Student $student): bool
    {
        if ($user->can('create:hostel-queries')) {
            return true;
        }

        return HmsStudentAccess::canManageOwnAccommodation($user)
            && HmsStudentAccess::ownsStudent($user, $student);
    }

    public function update(User $user, HostelQuery $hostelQuery): bool
    {
        return $user->can('update:hostel-queries', $hostelQuery);
    }

    public function delete(User $user, HostelQuery $hostelQuery): bool
    {
        return $user->can('delete:hostel-queries', $hostelQuery);
    }

    public function restore(User $user, HostelQuery $hostelQuery): bool
    {
        return $user->can('restore:hostel-queries', $hostelQuery);
    }

    public function forceDelete(User $user, HostelQuery $hostelQuery): bool
    {
        return $user->can('forceDelete:hostel-queries', $hostelQuery);
    }
}
