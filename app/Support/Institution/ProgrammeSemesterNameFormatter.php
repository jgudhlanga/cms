<?php

declare(strict_types=1);

namespace App\Support\Institution;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;

final class ProgrammeSemesterNameFormatter
{
    public static function stageName(string $levelName, int $stageNumber): string
    {
        $level = self::normaliseLevelName($levelName);

        return "{$level} {$stageNumber}";
    }

    public static function stageCode(string $levelName, int $stageNumber): string
    {
        $compact = (string) preg_replace('/\s+/', '', self::normaliseLevelName($levelName));

        return $compact.$stageNumber;
    }

    public static function taughtName(
        AcademicCalendarTypeEnum $calendarType,
        int $yearNumber,
        int $periodInYear,
        string $levelName = '',
    ): string {
        $periodLabel = self::periodLabel($calendarType);

        return self::stageName($levelName, $yearNumber).' '.$periodLabel.' '.$periodInYear;
    }

    public static function attachmentName(
        int $yearNumber,
        int $periodInYear,
        string $levelName = '',
        ?AcademicCalendarTypeEnum $calendarType = null,
    ): string {
        $periodLabel = $calendarType instanceof AcademicCalendarTypeEnum
            ? self::periodLabel($calendarType)
            : 'Sem';

        return self::stageName($levelName, $yearNumber).' '.$periodLabel.' '.$periodInYear;
    }

    public static function periodLabel(AcademicCalendarTypeEnum $calendarType): string
    {
        return match ($calendarType) {
            AcademicCalendarTypeEnum::TERM => 'Term',
            AcademicCalendarTypeEnum::ABMA => 'ABMA',
            AcademicCalendarTypeEnum::SEMESTER => 'Sem',
        };
    }

    public static function periodsPerYear(AcademicCalendarTypeEnum $calendarType): int
    {
        return $calendarType->maxAssessmentCalendarsPerYear();
    }

    /**
     * Compact form for dense listings: "NC 1 Sem 2" -> "NC 1 S2",
     * "Year 2 Attachment 1" -> "Y2 Att 1", "NC 1 Term 3" -> "NC 1 T3".
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
     * Phase names already include the level (`NC 1 Sem 1`). Prefix only when they do not.
     */
    public static function qualifiedName(?string $levelName, ?string $name, bool $short = false): string
    {
        $phase = $short ? self::shortName($name) : trim((string) $name);
        $levelName = trim((string) $levelName);

        if ($phase === '') {
            return $levelName;
        }

        if ($levelName === '' || str_starts_with($phase, $levelName)) {
            return $phase;
        }

        return "{$levelName} {$phase}";
    }

    private static function normaliseLevelName(string $levelName): string
    {
        $level = trim($levelName);

        return $level === '' ? 'Year' : $level;
    }
}
