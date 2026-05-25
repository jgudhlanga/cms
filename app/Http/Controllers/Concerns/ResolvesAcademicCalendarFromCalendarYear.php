<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AcademicCalendars\AcademicCalendar;

trait ResolvesAcademicCalendarFromCalendarYear
{
    protected function academicCalendarFromCalendarYear(string $calendarYear): AcademicCalendar
    {
        $id = AcademicCalendar::resolveCanonicalIdForCalendarYear($calendarYear);
        abort_if($id === null, 404);

        return AcademicCalendar::query()->findOrFail($id);
    }
}
