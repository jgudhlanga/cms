<?php

namespace App\Policies\AcademicCalendars;

use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Users\User;

class AcademicCalendarPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:academic-calendars');
    }

    public function view(User $user, AcademicCalendar $academicCalendar): bool
    {
        return $user->can('viewAny:academic-calendars') || $user->can('view:academic-calendars');
    }

    public function create(User $user): bool
    {
        return $user->can('create:academic-calendars');
    }

    public function update(User $user, AcademicCalendar $academicCalendar): bool
    {
        return $user->can('update:academic-calendars', $academicCalendar);
    }

    public function delete(User $user, AcademicCalendar $academicCalendar): bool
    {
        return $user->can('delete:academic-calendars', $academicCalendar);
    }

    public function restore(User $user, AcademicCalendar $academicCalendar): bool
    {
        return $user->can('restore:academic-calendars', $academicCalendar);
    }

    public function forceDelete(User $user, AcademicCalendar $academicCalendar): bool
    {
        return $user->can('forceDelete:academic-calendars', $academicCalendar);
    }

    public function export(User $user): bool
    {
        return $user->can('export:academic-calendars');
    }
}
