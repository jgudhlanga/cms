<?php

namespace App\Http\Filters\Institution;

use App\Http\Filters\QueryFilter;

class DepartmentFilter extends QueryFilter
{
    protected array $sortable = [
        'createdAt' => 'created_at',
        'name',
        'updatedAt' => 'updated_at'
    ];

    protected  array $searchable =['name'];

}
