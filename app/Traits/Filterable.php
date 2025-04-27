<?php

namespace App\Traits;

use App\Http\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

trait Filterable 
{
    public function scopeFilter($query, QueryFilter $filters): Builder
    {
        return $filters->apply($query);
    }
}
