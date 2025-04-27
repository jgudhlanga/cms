<?php

namespace App\Http\Filters\Shared;

use App\Http\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

class SharedNameFilter extends QueryFilter
{
    protected array $sortable = [
        'createdAt' => 'created_at',
        'name',
        'updatedAt' => 'updated_at'
    ];

	protected array $searchable = ['name'];
}
