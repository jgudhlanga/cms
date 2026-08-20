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

    /**
     * @return list<string>
     */
    public function allowedSemesterSlugs(): array
    {
        $slugs = [];

        for ($number = 1; $number <= $this->maxAssessmentCalendarsPerYear(); $number++) {
            $slugs[] = $this->value.'-'.$number;
        }

        return $slugs;
    }

    public function fallbackSemesterSlug(): string
    {
        return $this->value.'-1';
    }
}
