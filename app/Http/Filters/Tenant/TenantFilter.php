<?php

namespace App\Http\Filters\Tenant;

use App\Http\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

class TenantFilter extends QueryFilter
{
    protected array $sortable = [
        'createdAt' => 'created_at',
        'name',
        'updatedAt' => 'updated_at'
    ];
    public function name($value): Builder
    {
        return $this->builder->where('name', 'LIKE', '%' . $value . '%');
    }
}
