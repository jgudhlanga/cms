<?php

namespace App\Http\Filters\Users;

use App\Http\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

class UserFilter extends QueryFilter
{
    protected array $sortable = [
        'createdAt' => 'created_at',
        'tenant' => 'tenant_id',
        'updatedAt' => 'updated_at'
    ];

    protected array $searchable = ['first_name', 'middle_name', 'last_name', 'email'];

    protected array $only = ['departments', 'roles'];

    public function roles($value): Builder
    {
        $only = $value;
        if (is_string($only)) {
            $only = explode(',', $only);
        }
        return $this->builder->whereHas('roles', function ($query) use ($only) {
            $query->whereIn('slug', $only);
        });
    }
}
