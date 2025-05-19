<?php

namespace App\Http\Filters\Applications;

use App\Http\Filters\QueryFilter;

class ApplicationFilter extends QueryFilter
{
    protected array $sortable = [
        'createdAt' => 'created_at',
        'name',
        'updatedAt' => 'updated_at'
    ];

    protected  array $searchable =['user_id'];

}
