<?php

namespace App\Services\Assessments;

use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentCalendar\DepartmentAssessmentCalendar;
use Carbon\Carbon;

/**
 * Department windows must always sit inside their global window. When the global dates change,
 * department dates that fall outside are pulled back inside (the activity log records the change).
 */
class DepartmentCalendarSyncService
{
    /**
     * @return list<DepartmentAssessmentCalendar> The department calendars whose dates were changed.
     */
    public function clampToGlobalWindow(AssessmentCalendar $calendar): array
    {
        $globalStart = Carbon::parse($calendar->start_date)->startOfDay();
        $globalEnd = Carbon::parse($calendar->end_date)->startOfDay();
        $changed = [];

        foreach ($calendar->departmentCalendars()->get() as $departmentCalendar) {
            $start = Carbon::parse($departmentCalendar->start_date)->startOfDay();
            $end = Carbon::parse($departmentCalendar->end_date)->startOfDay();

            $clampedStart = $start->lt($globalStart) || $start->gt($globalEnd) ? $globalStart->copy() : $start;
            $clampedEnd = $end->gt($globalEnd) || $end->lt($globalStart) ? $globalEnd->copy() : $end;

            if ($clampedStart->gt($clampedEnd)) {
                $clampedStart = $globalStart->copy();
                $clampedEnd = $globalEnd->copy();
            }

            if ($clampedStart->equalTo($start) && $clampedEnd->equalTo($end)) {
                continue;
            }

            $departmentCalendar->update([
                'start_date' => $clampedStart->toDateString(),
                'end_date' => $clampedEnd->toDateString(),
            ]);

            $changed[] = $departmentCalendar;
        }

        return $changed;
    }
}
