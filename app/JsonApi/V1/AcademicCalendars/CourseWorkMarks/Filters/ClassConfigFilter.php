<?php

namespace App\JsonApi\V1\AcademicCalendars\CourseWorkMarks\Filters;

use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\DeserializesValue;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class ClassConfigFilter implements Filter
{
    use DeserializesValue, IsSingular;

    private const string KEY = 'classConfig';

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
        $classConfigId = (int) $value;

        $classIds = AcademicCalendarClass::query()
            ->where('class_config_id', $classConfigId)
            ->whereNull('deleted_at')
            ->pluck('id');

        $enrolmentIds = AcademicCalendarStudentEnrolment::query()
            ->whereIn('academic_calendar_class_id', $classIds)
            ->whereNull('deleted_at')
            ->pluck('student_enrolment_id');

        return $query->whereIn('student_enrolment_id', $enrolmentIds);
    }
}
