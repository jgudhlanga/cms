<?php

namespace App\Http\Filters\Users;

use App\Http\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

class UserFilter extends QueryFilter
{
    protected array $sortable = [
        'createdAt' => 'created_at',
        'name',
        'tenant' => 'tenant_id',
        'updatedAt' => 'updated_at'
    ];

    public function name($value): Builder
    {
        return $this->builder->where('name', 'LIKE', '%' . $value . '%');
    }

    public function email($value): Builder
    {
        return $this->builder->where('email', 'LIKE', '%' . $value . '%');
    }

    public function tenant($value): Builder
    {
        return $this->builder->where('tenant_id', $value);
    }
}
