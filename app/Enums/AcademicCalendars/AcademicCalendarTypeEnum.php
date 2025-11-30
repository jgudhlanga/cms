<?php

namespace App\Enums\AcademicCalendars;

enum AcademicCalendarTypeEnum: string
{
    case SEMESTER = 'semester';
    case TRIMESTER = 'trimester';
    case QUADMESTER = 'quadmester';
    case QUARTER = 'quarter';
    case BLOCK = 'block';
    case MODULAR = 'modular';
    case MINIMESTER = 'minimester';
    case OTHER = 'other';
}
