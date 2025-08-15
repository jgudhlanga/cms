<?php

namespace App\Http\Filters\Students;

use App\Http\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

class StudentAcademicResultFilter extends QueryFilter
{
    protected array $sortable = [
        'createdAt' => 'created_at',
        'name',
        'tenant' => 'tenant_id',
        'updatedAt' => 'updated_at'
    ];
}
