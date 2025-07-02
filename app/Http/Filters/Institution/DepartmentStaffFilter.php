<?php

namespace App\Http\Filters\Institution;

use App\Http\Filters\QueryFilter;

class DepartmentStaffFilter extends QueryFilter
{
    protected array $sortable = [
        'createdAt' => 'created_at',
        'updatedAt' => 'updated_at'
    ];

    protected  array $searchable =['start_date'];

}
