<?php

namespace App\Http\Filters\AcademicCalendars;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Http\Filters\Shared\SharedNameFilter;

class SemesterFilter extends SharedNameFilter
{
    public function calendar_type(string $value): void
    {
        $enums = [];

        foreach (array_filter(array_map('trim', explode(',', $value))) as $part) {
            $enum = AcademicCalendarTypeEnum::tryFrom($part);
            if ($enum instanceof AcademicCalendarTypeEnum) {
                $enums[] = $enum;
            }
        }

        if ($enums === []) {
            return;
        }

        $this->builder->where(function ($query) use ($enums): void {
            foreach ($enums as $index => $enum) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $query->{$method}('slug', 'like', $enum->value.'-%');
            }
        });
    }
}
