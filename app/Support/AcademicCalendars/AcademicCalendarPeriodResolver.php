<?php

namespace App\Support\AcademicCalendars;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use Carbon\Carbon;
use Carbon\CarbonInterface;

final class AcademicCalendarPeriodResolver
{
    /**
     * ABMA period number 1–4 from the calendar quarter of the opening date.
     */
    public static function abmaPeriodNumberFromOpening(CarbonInterface $opening): int
    {
        $month = (int) $opening->month;

        return (int) ceil($month / 3);
    }

    /**
     * 1-based index of this calendar among siblings with the same calendar year and type (opening_date asc, id asc).
     */
    public static function semesterOrTermOrdinal(AcademicCalendar $row): int
    {
        $beforeCount = AcademicCalendar::query()
            ->where('calendar_year', $row->calendar_year)
            ->where('type', $row->type)
            ->where(function ($query) use ($row): void {
                $query->where('opening_date', '<', $row->opening_date)
                    ->orWhere(function ($inner) use ($row): void {
                        $inner->where('opening_date', '=', $row->opening_date)
                            ->where('id', '<', $row->id);
                    });
            })
            ->count();

        return $beforeCount + 1;
    }

    public static function academicYearOptionSlugForCalendar(AcademicCalendar $row): string
    {
        $opening = Carbon::parse($row->opening_date)->startOfDay();

        return match ($row->type) {
            AcademicCalendarTypeEnum::ABMA => 'abma-'.self::abmaPeriodNumberFromOpening($opening),
            AcademicCalendarTypeEnum::SEMESTER => 'semester-'.self::semesterOrTermOrdinal($row),
            AcademicCalendarTypeEnum::TERM => 'term-'.self::semesterOrTermOrdinal($row),
        };
    }

    public static function displayPeriodLabel(AcademicCalendar $row): string
    {
        $opening = Carbon::parse($row->opening_date);

        return match ($row->type) {
            AcademicCalendarTypeEnum::ABMA => 'ABMA '.self::abmaPeriodNumberFromOpening($opening),
            AcademicCalendarTypeEnum::SEMESTER => 'Semester '.self::semesterOrTermOrdinal($row),
            AcademicCalendarTypeEnum::TERM => 'Term '.self::semesterOrTermOrdinal($row),
        };
    }

    public static function displayPeriodLabelFromOpening(AcademicCalendarTypeEnum $type, CarbonInterface $opening): string
    {
        return match ($type) {
            AcademicCalendarTypeEnum::ABMA => 'ABMA '.self::abmaPeriodNumberFromOpening($opening),
            AcademicCalendarTypeEnum::SEMESTER => 'Semester 1',
            AcademicCalendarTypeEnum::TERM => 'Term 1',
        };
    }
}
