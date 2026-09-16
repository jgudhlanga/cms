<?php

declare(strict_types=1);

namespace App\Services\Students\StudyPosition;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;

/**
 * The calendar period students of one calendar type are confirming for, e.g. "2026 · Semester 2".
 */
final readonly class CurrentStudyPeriod
{
    /**
     * @param  list<int>  $yearPeriodIds  Every period of this type in the same calendar year; year
     *                                    enrolments point at any one of them.
     */
    public function __construct(
        public AcademicCalendarTypeEnum $type,
        public AcademicCalendar $period,
        public Semester $slot,
        public string $calendarYear,
        public string $label,
        public string $slotName,
        public array $yearPeriodIds,
    ) {}

    public function periodId(): int
    {
        return (int) $this->period->id;
    }
}
