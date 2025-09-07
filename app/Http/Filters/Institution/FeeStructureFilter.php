<?php

namespace App\Http\Filters\Institution;

use App\Http\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

class FeeStructureFilter extends QueryFilter
{
    protected array $sortable = [
        'createdAt' => 'created_at',
        'updatedAt' => 'updated_at'
    ];

    protected array $searchable = ['amount', 'local_fca_amount'];
}
