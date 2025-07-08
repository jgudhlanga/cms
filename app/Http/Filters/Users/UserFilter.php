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

    protected array $searchable = ['name', 'email'];
}
