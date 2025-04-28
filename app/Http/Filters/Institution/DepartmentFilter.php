<?php

namespace App\Http\Filters\Institution;

use App\Http\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

class DepartmentFilter extends QueryFilter
{
    protected array $sortable = [
        'createdAt' => 'created_at',
        'name',
        'updatedAt' => 'updated_at'
    ];

    protected array $routeModels = ['bank'];

    protected  array $searchable =['name'];

    public function bank(): Builder
    {
        $bank = Route::input('bank');
        return $this->builder->where('bank_id', '=', $bank->id);
    }
}
