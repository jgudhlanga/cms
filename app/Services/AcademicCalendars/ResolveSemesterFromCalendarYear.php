<?php

namespace App\Services\AcademicCalendars;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class ResolveSemesterFromCalendarYear
{
    private readonly CarbonInterface $now;

    public function __construct(?CarbonInterface $now = null)
    {
        $this->now = $now ?? Carbon::now();
    }

    public function resolveSemesterId(string $calendarYear): ?int
    {
        return $this->resolveForCalendarType($calendarYear, AcademicCalendarTypeEnum::SEMESTER);
    }

    public function resolveForCalendarType(string $calendarYear, AcademicCalendarTypeEnum $type): ?int
    {
        $today = $this->now->copy()->startOfDay();

        $active = AcademicCalendar::query()
            ->where('calendar_year', $calendarYear)
            ->where('type', $type)
            ->whereDate('opening_date', '<=', $today)
            ->whereDate('closing_date', '>=', $today)
            ->orderByDesc('opening_date')
            ->orderByDesc('id')
            ->first();

        if (! $active instanceof AcademicCalendar) {
            return null;
        }

        $slug = AcademicCalendarPeriodResolver::semesterSlugForCalendar($active);

        $id = Semester::query()->where('slug', $slug)->value('id');

        return $id !== null ? (int) $id : null;
    }
}
