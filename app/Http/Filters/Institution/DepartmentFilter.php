<?php

namespace App\Http\Filters\Institution;

use App\Http\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

class DepartmentFilter extends QueryFilter
{
    protected array $sortable = [
        'createdAt' => 'created_at',
        'name',
        'updatedAt' => 'updated_at'
    ];

    protected array $searchable = ['name', 'is_academic'];

    public function is_academic($value): Builder
    {
        return $this->builder->where('is_academic', $value);
    }
}
