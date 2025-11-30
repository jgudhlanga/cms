<?php

namespace App\Policies\AcademicCalendars;

use App\Enums\Acl\PermissionEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Users\User;

class AcademicCalendarPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_ACADEMIC_CALENDARS);
    }

    public function view(User $user, AcademicCalendar $academicCalendar): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_ACADEMIC_CALENDARS) || $user->can(PermissionEnum::VIEW_ACADEMIC_CALENDARS);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::CREATE_ACADEMIC_CALENDARS);
    }

    public function update(User $user, AcademicCalendar $academicCalendar): bool
    {
        return $user->can(PermissionEnum::UPDATE_ACADEMIC_CALENDARS, $academicCalendar);
    }

    public function delete(User $user, AcademicCalendar $academicCalendar): bool
    {
        return $user->can(PermissionEnum::DELETE_ACADEMIC_CALENDARS, $academicCalendar);
    }

    public function restore(User $user, AcademicCalendar $academicCalendar): bool
    {
        return $user->can(PermissionEnum::RESTORE_ACADEMIC_CALENDARS, $academicCalendar);
    }

    public function forceDelete(User $user, AcademicCalendar $academicCalendar): bool
    {
        return $user->can(PermissionEnum::FORCE_DELETE_ACADEMIC_CALENDARS, $academicCalendar);
    }
}
