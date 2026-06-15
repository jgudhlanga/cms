<?php

namespace App\Services\AcademicCalendars;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class ResolveAcademicYearOptionFromCalendarYear
{
    private readonly CarbonInterface $now;

    public function __construct(?CarbonInterface $now = null)
    {
        $this->now = $now ?? Carbon::now();
    }

    public function resolveAcademicYearOptionId(string $calendarYear): ?int
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

        $slug = AcademicCalendarPeriodResolver::academicYearOptionSlugForCalendar($active);

        $id = AcademicYearOption::query()->where('slug', $slug)->value('id');

        return $id !== null ? (int) $id : null;
    }
}
