<?php

namespace App\Http\Filters\Institution;

use App\Http\Filters\QueryFilter;

class AssessmentCalendarFilter extends QueryFilter
{
    protected array $sortable = [
        'startDate' => 'start_date',
        'endDate' => 'end_date',
        'type',
        'createdAt' => 'created_at',
        'updatedAt' => 'updated_at',
    ];

    protected array $searchable = ['type'];
}
