<?php

namespace App\Http\Filters\Shared;

use App\Http\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

class SharedTitleFilter extends QueryFilter
{
    protected array $sortable = [
        'createdAt' => 'created_at',
        'title' => 'title',
        'updatedAt' => 'updated_at'
    ];

	protected array $searchable = ['title'];
}
