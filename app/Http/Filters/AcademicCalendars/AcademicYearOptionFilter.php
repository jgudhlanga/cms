<?php

namespace App\Http\Filters\AcademicCalendars;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Http\Filters\Shared\SharedNameFilter;

class AcademicYearOptionFilter extends SharedNameFilter
{
    public function calendar_type(string $value): void
    {
        $enum = AcademicCalendarTypeEnum::tryFrom($value);

        if (! $enum instanceof AcademicCalendarTypeEnum) {
            return;
        }

        $this->builder->where('slug', 'like', $enum->value.'-%');
    }
}
