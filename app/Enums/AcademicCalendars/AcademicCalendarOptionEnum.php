<?php

namespace App\Enums\AcademicCalendars;

enum AcademicCalendarOptionEnum: string
{
    case SEMESTER_ONE = 'Semester 1 (one)';
    case SEMESTER_TWO = 'Semester 2 (two)';
    case TERM_ONE = 'Term 1 (one)';
    case TERM_TWO = 'Term 2 (two)';
    case TERM_THREE = 'Term 3 (three)';
    case TERM_FOUR = 'Term 4 (four)';
    case OTHER = 'Other';
}
