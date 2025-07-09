<?php

namespace App\Http\Filters\Institution;

use App\Http\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

class StaffFilter extends QueryFilter
{
    protected array $sortable = [
        'createdAt' => 'created_at',
        'updatedAt' => 'updated_at'
    ];


    protected array $routeModels = ['institution_department'];

    protected array $joins = ['user'];
    protected array $only = ['departments', 'roles'];

    public function institution_department(): Builder
    {
        $institutionDepartment = Route::input('institution_department');
        if (!empty($institutionDepartment)) {
            return $this->builder->whereHas('institutionDepartments', function ($q) use ($institutionDepartment) {
                $q->where('institution_department_id', $institutionDepartment->id);
            });
        };
        return $this->builder;
    }

    public function roles($value): Builder
    {
        $only = $value;
        if (is_string($only)) {
            $only = explode(',', $only);
        }
        return $this->builder->whereHas('user', function ($query) use ($only) {
            $query->whereHas('roles', function ($roleQuery) use ($only) {
                $roleQuery->whereIn('slug', $only);
            });
        });
    }

    public function user($value): Builder
    {
        return $this->builder->whereHas('user', function ($query) use ($value) {
            $query->where(function ($q) use ($value) {
                $q->where('first_name', 'like', "%{$value}%")
                    ->orWhere('middle_name', 'like', "%{$value}%")
                    ->orWhere('last_name', 'like', "%{$value}%")
                    ->orWhere('email', 'like', "%{$value}%");
            });
        });
    }
}
