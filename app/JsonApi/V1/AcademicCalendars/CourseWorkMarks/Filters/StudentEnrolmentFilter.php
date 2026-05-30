<?php

namespace App\JsonApi\V1\AcademicCalendars\CourseWorkMarks\Filters;

use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\DeserializesValue;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class StudentEnrolmentFilter implements Filter
{
    use DeserializesValue, IsSingular;

    private const string KEY = 'studentEnrolment';

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
        return $query->where('student_enrolment_id', (int) $value);
    }
}
