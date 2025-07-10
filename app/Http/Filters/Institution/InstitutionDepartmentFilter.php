<?php

namespace App\Http\Filters\Institution;

use App\Http\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

class InstitutionDepartmentFilter extends QueryFilter
{
    protected array $sortable = [
        'createdAt' => 'created_at',
        'name',
        'updatedAt' => 'updated_at'
    ];

    protected array $joins = ['academic'];

    public function academic($value): Builder
    {
        return $this->builder->whereHas('department', function ($query) use ($value) {
            $query->where('is_academic', (int)$value);
        });
    }
}
