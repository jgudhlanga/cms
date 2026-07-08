<?php

namespace App\Enums\AcademicCalendars;

enum AcademicCalendarTypeEnum: string
{
    case TERM = 'term';
    case SEMESTER = 'semester';
    case ABMA = 'abma';

    public function maxAssessmentCalendarsPerYear(): int
    {
        return match ($this) {
            self::SEMESTER => 2,
            self::TERM => 3,
            self::ABMA => 4,
        };
    }
}
