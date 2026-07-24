<?php

namespace App\Http\Filters\Rbac;

use App\Http\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class PermissionFilter extends QueryFilter
{
    protected array $sortable = [
        'createdAt' => 'created_at',
        'description',
        'name',
        'guardName' => 'guard_name',
        'updatedAt' => 'updated_at',
    ];

    protected array $routeModels = ['role'];

    protected array $searchable = ['name', 'description', 'guard_name'];

    /**
     * Filter permissions by a given role slug or ID.
     */
    public function role($value): Builder
    {
        $role = $this->request->route('role');

        if ($role instanceof Role) {
            $roleName = $role->name;
        } else {
            $roleName = is_string($value) ? $value : null;
        }

        $roleName = is_string($roleName) ? trim($roleName) : null;

        if ($roleName === null || $roleName === '') {
            return $this->builder;
        }

        return $this->builder->whereHas('roles', function (Builder $query) use ($roleName) {
            $query->where('name', $roleName)
                ->orWhere('slug', $roleName)
                ->orWhere('id', $roleName);
        });
    }
}
