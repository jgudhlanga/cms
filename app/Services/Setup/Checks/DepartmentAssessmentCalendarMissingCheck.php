<?php

declare(strict_types=1);

namespace App\Services\Setup\Checks;

use App\Contracts\Setup\SetupGapCheck;
use App\Enums\Setup\SetupGapCheckEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Services\Setup\Checks\Concerns\ResolvesDepartments;
use App\Support\Setup\SetupGapResult;
use Illuminate\Support\Facades\DB;

/**
 * Departments teaching this period that have not set up their own assessment calendar. Without one, the
 * department's assessment windows never open or close and its mark reminders never run.
 */
class DepartmentAssessmentCalendarMissingCheck implements SetupGapCheck
{
    use ResolvesDepartments;

    public function key(): SetupGapCheckEnum
    {
        return SetupGapCheckEnum::DEPARTMENT_ASSESSMENT_CALENDAR_MISSING;
    }

    /**
     * @return iterable<SetupGapResult>
     */
    public function run(): iterable
    {
        $academicCalendar = AcademicCalendar::resolveSemesterForDate();
        $calendarYear = (string) $academicCalendar->calendar_year;

        $teaching = DB::table('class_configs')
            ->where('calendar_year', $calendarYear)
            ->where('status', 'open')
            ->whereNotNull('institution_department_id')
            ->groupBy('institution_department_id')
            ->select(['institution_department_id', DB::raw('COUNT(*) as class_count')])
            ->get();

        $assessmentCalendars = DB::table('assessment_calendars as ac')
            ->leftJoin('assessment_types as at', 'at.id', '=', 'ac.assessment_type_id')
            ->where('ac.academic_calendar_id', $academicCalendar->id)
            ->select(['ac.id', 'ac.tenant_id', 'at.name as assessment_name'])
            ->get();

        if ($teaching->isEmpty() || $assessmentCalendars->isEmpty()) {
            return [];
        }

        $departments = $this->departments(
            $teaching->pluck('institution_department_id')->map(fn ($id): int => (int) $id)->unique()->values()->all(),
        );

        $existing = DB::table('department_assessment_calendars')
            ->whereIn('assessment_calendar_id', $assessmentCalendars->pluck('id'))
            ->select(['assessment_calendar_id', 'institution_department_id'])
            ->get()
            ->map(fn (object $row): string => "{$row->assessment_calendar_id}:{$row->institution_department_id}")
            ->flip();

        $gaps = [];

        foreach ($teaching as $row) {
            $departmentId = (int) $row->institution_department_id;
            $department = $departments[$departmentId] ?? null;

            if ($department === null) {
                continue;
            }

            foreach ($assessmentCalendars as $calendar) {
                if ($existing->has("{$calendar->id}:{$departmentId}")) {
                    continue;
                }

                $assessmentName = (string) ($calendar->assessment_name ?? __('trans.assessment_type'));

                $gaps[] = new SetupGapResult(
                    check: $this->key(),
                    tenantId: (int) $calendar->tenant_id,
                    title: __('setup_gaps.department_assessment_calendar_missing_title', [
                        'department' => $department['name'],
                        'calendar' => $assessmentName,
                    ]),
                    body: __('setup_gaps.department_assessment_calendar_missing_body', [
                        'count' => (int) $row->class_count,
                    ]),
                    institutionDepartmentId: $departmentId,
                    url: $this->departmentClassesUrl($departmentId, $calendarYear),
                    meta: [
                        'assessmentCalendarId' => (int) $calendar->id,
                        'calendarYear' => $calendarYear,
                        'classCount' => (int) $row->class_count,
                    ],
                    fingerprintKey: "assessment-calendar:{$calendar->id}",
                );
            }
        }

        return $gaps;
    }
}
