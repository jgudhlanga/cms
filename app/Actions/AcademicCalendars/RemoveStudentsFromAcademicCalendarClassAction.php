<?php

declare(strict_types=1);

namespace App\Actions\AcademicCalendars;

use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RemoveStudentsFromAcademicCalendarClassAction
{
    /**
     * @param  Collection<int, AcademicCalendarStudentEnrolment>  $enrolments
     */
    public function execute(Collection $enrolments): int
    {
        return (int) DB::transaction(function () use ($enrolments): int {
            $removed = 0;

            foreach ($enrolments as $enrolment) {
                $enrolment->delete();
                $removed++;
            }

            return $removed;
        });
    }
}
