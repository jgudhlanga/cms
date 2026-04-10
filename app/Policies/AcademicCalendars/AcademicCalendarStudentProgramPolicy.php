<?php

namespace App\Policies\AcademicCalendars;

use App\Models\AcademicCalendars\AcademicCalendarStudentProgram;
use App\Models\Users\User;

class AcademicCalendarStudentProgramPolicy
{
    public function update(User $user, AcademicCalendarStudentProgram $academicCalendarStudentProgram): bool
    {
        return $user->can('update:academic-calendar-student-programs');
    }
}
