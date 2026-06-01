<?php

namespace App\JsonApi\V1\AcademicCalendars\CourseWorkMarks\Filters;

use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\DeserializesValue;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class AcademicCalendarClassFilter implements Filter
{
    use DeserializesValue, IsSingular;

    private const string KEY = 'academicCalendarClass';

    public static function make(): self
    {
        return new self;
    }

    public function key(): string
    {
        return self::KEY;
    }

    public function apply($query, $value): Builder
    {
        $classId = (int) $value;

        $enrolmentIds = AcademicCalendarStudentEnrolment::query()
            ->where('academic_calendar_class_id', $classId)
            ->whereNull('deleted_at')
            ->pluck('student_enrolment_id');

        return $query->whereIn('student_enrolment_id', $enrolmentIds);
    }
}
