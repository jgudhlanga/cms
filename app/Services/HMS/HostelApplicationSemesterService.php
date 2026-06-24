<?php

namespace App\Services\HMS;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class HostelApplicationSemesterService
{
    public const BLOCKER_NO_RUNNING_SEMESTER = 'no_running_semester';

    public const BLOCKER_CALENDAR_YEAR_MISSING = 'calendar_year_missing';

    public function resolveCalendarYear(StudentEnrolment $enrolment): ?string
    {
        $calendarYear = $enrolment->studentApplication?->intakePeriod?->calendar_year;

        if ($calendarYear === null || $calendarYear === '') {
            return null;
        }

        return (string) $calendarYear;
    }

    public function resolveRunningSemester(string $calendarYear, ?CarbonInterface $asOf = null): ?AcademicCalendar
    {
        $asOf = $asOf ?? Carbon::now((string) config('app.timezone'));
        $today = $asOf->copy()->startOfDay();

        return AcademicCalendar::query()
            ->where('calendar_year', $calendarYear)
            ->where('type', AcademicCalendarTypeEnum::SEMESTER)
            ->whereDate('opening_date', '<=', $today)
            ->whereDate('closing_date', '>=', $today)
            ->orderByDesc('opening_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return array{
     *     success: bool,
     *     blocker: string|null,
     *     checkIn: string|null,
     *     checkOut: string|null,
     *     label: string|null,
     * }
     */
    public function datesForApplication(Student $student, ?CarbonInterface $asOf = null): array
    {
        $enrolment = $student->latestEnrolment;

        if ($enrolment === null) {
            return $this->failure(self::BLOCKER_CALENDAR_YEAR_MISSING);
        }

        $calendarYear = $this->resolveCalendarYear($enrolment);

        if ($calendarYear === null) {
            return $this->failure(self::BLOCKER_CALENDAR_YEAR_MISSING);
        }

        $semester = $this->resolveRunningSemester($calendarYear, $asOf);

        if ($semester === null) {
            return $this->failure(self::BLOCKER_NO_RUNNING_SEMESTER);
        }

        return [
            'success' => true,
            'blocker' => null,
            'checkIn' => Carbon::parse($semester->opening_date)->toDateString(),
            'checkOut' => Carbon::parse($semester->closing_date)->toDateString(),
            'label' => AcademicCalendarPeriodResolver::displayPeriodLabel($semester),
        ];
    }

    /**
     * @return array{success: bool, blocker: string|null, checkIn: null, checkOut: null, label: null}
     */
    private function failure(string $blocker): array
    {
        return [
            'success' => false,
            'blocker' => $blocker,
            'checkIn' => null,
            'checkOut' => null,
            'label' => null,
        ];
    }
}
