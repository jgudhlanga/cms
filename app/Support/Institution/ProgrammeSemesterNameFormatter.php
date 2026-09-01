<?php

declare(strict_types=1);

namespace App\Support\Institution;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;

final class ProgrammeSemesterNameFormatter
{
    public static function taughtName(
        AcademicCalendarTypeEnum $calendarType,
        int $yearNumber,
        int $periodInYear,
    ): string {
        $periodLabel = match ($calendarType) {
            AcademicCalendarTypeEnum::TERM => 'Term',
            AcademicCalendarTypeEnum::ABMA => 'ABMA',
            AcademicCalendarTypeEnum::SEMESTER => 'Sem',
        };

        return "Year {$yearNumber} {$periodLabel} {$periodInYear}";
    }

    public static function attachmentName(int $yearNumber, int $periodInYear): string
    {
        return "Year {$yearNumber} Attachment {$periodInYear}";
    }

    public static function periodsPerYear(AcademicCalendarTypeEnum $calendarType): int
    {
        return $calendarType->maxAssessmentCalendarsPerYear();
    }
}
