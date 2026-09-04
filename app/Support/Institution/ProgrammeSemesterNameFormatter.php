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

    /**
     * Compact form for dense listings: "Year 1 Sem 2" -> "Y1 S2",
     * "Year 2 Attachment 1" -> "Y2 Att 1", "Year 1 Term 3" -> "Y1 T3".
     */
    public static function shortName(?string $name): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return '';
        }

        $short = preg_replace(
            ['/\bYear\s+(\d+)\b/i', '/\bSem\s*(\d+)\b/i', '/\bTerm\s*(\d+)\b/i', '/\bABMA\s*(\d+)\b/i', '/\bAttachment\s*(\d+)\b/i'],
            ['Y$1', 'S$1', 'T$1', 'A$1', 'Att $1'],
            $name,
        );

        return is_string($short) ? $short : $name;
    }

    /**
     * Every level restarts its numbering at Year 1, so a phase name only identifies a phase once
     * the level is attached to it.
     */
    public static function qualifiedName(?string $levelName, ?string $name, bool $short = false): string
    {
        $phase = $short ? self::shortName($name) : trim((string) $name);
        $levelName = trim((string) $levelName);

        if ($phase === '') {
            return $levelName;
        }

        return $levelName === '' ? $phase : "{$levelName} {$phase}";
    }
}
