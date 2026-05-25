<?php

namespace App\Enums\AcademicCalendars;

enum ClassMetaDataTypeEnum: string
{
    case LECTURER = 'lecturer';
    case TIME_TABLE = 'time-table';

    public function label(): string
    {
        return match ($this) {
            self::LECTURER => 'Lecturer',
            self::TIME_TABLE => 'Time table',
        };
    }
}
