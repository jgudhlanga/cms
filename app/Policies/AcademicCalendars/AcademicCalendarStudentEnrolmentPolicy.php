<?php

namespace App\Policies\AcademicCalendars;

use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\Users\User;

class AcademicCalendarStudentEnrolmentPolicy
{
    public function update(User $user, AcademicCalendarStudentEnrolment $academicCalendarStudentEnrolment): bool
    {
        return $user->can('update:academic-calendar-student-enrolments');
    }
}
