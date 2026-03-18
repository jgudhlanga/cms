<?php

namespace App\Enums\AcademicCalendars;

enum AcademicCalendarOptionEnum: string
{
    case SEMESTER_ONE = 'Semester 1';
    case SEMESTER_TWO = 'Semester 2';
    case TERM_ONE = 'Term 1';
    case TERM_TWO = 'Term 2';
    case TERM_THREE = 'Term 3';
    case TERM_FOUR = 'Term 4';
    case OTHER = 'Other';
}
