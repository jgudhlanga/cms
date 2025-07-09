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
    protected array $only = ['only'];

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

    public function only($value): Builder
    {
        $roles = [];
        $departments = [];

        // Parse roles
        if (isset($value['roles'])) {
            $roles = is_array($value['roles']) ? $value['roles'] : [$value['roles']];
            if (count($roles) === 1 && str_contains($roles[0], ',')) {
                $roles = explode(',', $roles[0]);
            }
            $roles = array_map('trim', $roles);
        }

        // Parse departments
        if (isset($value['departments'])) {
            $departments = is_array($value['departments']) ? $value['departments'] : [$value['departments']];
            if (count($departments) === 1 && str_contains($departments[0], ',')) {
                $departments = explode(',', $departments[0]);
            }
            $departments = array_map('intval', $departments); // cast to integers
        }

        return $this->builder
            ->when(!empty($departments), function ($query) use ($departments) {
                $query->whereHas('institutionDepartments', function ($q) use ($departments) {
                    $q->whereIn('institution_department_id', $departments);
                });
            })
            ->when(!empty($roles), function ($query) use ($roles) {
                $query->whereHas('user.roles', function ($q) use ($roles) {
                    $q->whereIn('slug', $roles);
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
