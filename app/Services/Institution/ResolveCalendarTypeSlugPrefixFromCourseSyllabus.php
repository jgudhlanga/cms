<?php

namespace App\Services\Institution;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\Institution\Syllabus\CourseSyllabus;

class ResolveCalendarTypeSlugPrefixFromCourseSyllabus
{
    /**
     * When a level has no calendar type, semester options are allowed (matches UI default).
     */
    public function resolve(int $courseSyllabusId): string
    {
        $courseSyllabus = CourseSyllabus::query()
            ->with('departmentLevelCourse.departmentLevel.level')
            ->find($courseSyllabusId);

        $calendarType = $courseSyllabus?->departmentLevelCourse?->departmentLevel?->level?->calendar_type;

        if ($calendarType instanceof AcademicCalendarTypeEnum) {
            return $calendarType->value;
        }

        return AcademicCalendarTypeEnum::tryFrom((string) $calendarType)?->value
            ?? AcademicCalendarTypeEnum::SEMESTER->value;
    }
}
