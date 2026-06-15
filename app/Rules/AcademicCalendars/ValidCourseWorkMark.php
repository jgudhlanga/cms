<?php

namespace App\Rules\AcademicCalendars;

use App\Support\AcademicCalendars\CourseWorkMarkValue;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCourseWorkMark implements ValidationRule
{
    public function __construct(
        private readonly bool $allowNull = false,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            if ($this->allowNull) {
                return;
            }

            $fail(__('academic_calendar.course_work_mark_required'));

            return;
        }

        if (! CourseWorkMarkValue::isValid($value)) {
            $fail(__('academic_calendar.course_work_mark_invalid'));
        }
    }
}
