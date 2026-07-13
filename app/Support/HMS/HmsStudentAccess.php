<?php

namespace App\Support\HMS;

use App\Models\HMS\HostelApplication;
use App\Models\HMS\HostelRoomAllocation;
use App\Models\Students\Student;
use App\Models\Users\User;

class HmsStudentAccess
{
    public static function canManageOwnAccommodation(User $user): bool
    {
        return $user->can('manageOwnStudentAccommodationDetails:students')
            || $user->can('manageOwnStudentPersonalDetails:students');
    }

    public static function ownsStudent(User $user, ?Student $student): bool
    {
        if ($student === null) {
            return false;
        }

        return $user->studentProfile?->id === $student->id;
    }

    public static function canViewStudentHms(User $user, Student $student): bool
    {
        if (self::canManageOwnAccommodation($user) && self::ownsStudent($user, $student)) {
            return true;
        }

        return $user->can('viewAny:hostel-applications')
            || $user->can('view:hostel-applications')
            || $user->can('viewAny:hostel-room-allocations')
            || $user->can('view:hostel-room-allocations')
            || $user->can('viewAny:students')
            || $user->can('view:students')
            || $user->can('view', $student);
    }

    public static function canCreateApplicationFor(User $user, Student $student): bool
    {
        if ($user->can('create:hostel-applications')) {
            return true;
        }

        return self::canManageOwnAccommodation($user) && self::ownsStudent($user, $student);
    }

    public static function canViewApplication(User $user, HostelApplication $application): bool
    {
        if ($application->student_id !== null
            && self::canManageOwnAccommodation($user)
            && self::ownsStudent($user, $application->student)) {
            return true;
        }

        return $user->can('viewAny:hostel-applications') || $user->can('view:hostel-applications');
    }

    public static function canViewAllocation(User $user, HostelRoomAllocation $allocation): bool
    {
        if ($allocation->student_id !== null
            && self::canManageOwnAccommodation($user)
            && self::ownsStudent($user, $allocation->student)) {
            return true;
        }

        return $user->can('viewAny:hostel-room-allocations') || $user->can('view:hostel-room-allocations');
    }

    public static function hasActiveHostelAllocation(Student $student): bool
    {
        return $student->activeHostelAllocation()->exists();
    }

    public static function isStaffHmsUser(User $user): bool
    {
        return $user->can('viewAny:hostel-applications')
            || $user->can('create:hostel-applications')
            || $user->can('viewAny:hostel-room-allocations');
    }

    public static function canViewCheckoutDates(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return self::isStaffHmsUser($user)
            || $user->can('update:hostel-applications')
            || $user->can('view:hostel-applications');
    }

    public static function studentIdFromRequest(): ?int
    {
        $studentId = request()->input('filter.student');

        if ($studentId === null || $studentId === '') {
            return null;
        }

        return (int) $studentId;
    }
}
