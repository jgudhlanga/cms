<?php

namespace App\Http\Filters\AcademicCalendars;

use App\Http\Filters\QueryFilter;

class AcademicCalendarFilter extends QueryFilter
{
    protected array $sortable = [
        'createdAt' => 'created_at',
        'description',
        'name',
        'updatedAt' => 'updated_at',
    ];
    protected array $searchable = ['name', 'description', 'calendar_year'];
}

