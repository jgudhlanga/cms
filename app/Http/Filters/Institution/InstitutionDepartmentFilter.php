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

    protected array $joins = ['is_academic'];

    public function is_academic($value): Builder
    {
        $search = request('search');
        if (is_null($search)) {
            return $this->builder->whereHas('department', function ($query) use ($value) {
                $query->where('is_academic', (int)$value);
            });
        } else {
            return $this->builder;
        }
    }

    public function search($value): void
    {
        $isAcademic = request('is_academic');
        $this->builder->whereHas('department', function ($query) use ($value, $isAcademic) {
            $query->where('name', 'like', '%' . $value . '%')
                ->where('is_academic', (int)$isAcademic);
        });
    }
}
