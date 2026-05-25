<?php

namespace App\Enums\AcademicCalendars;

enum AcademicCalendarTypeEnum: string
{
    case TERM = 'term';
    case SEMESTER = 'semester';
    case ABMA = 'abma';
}
