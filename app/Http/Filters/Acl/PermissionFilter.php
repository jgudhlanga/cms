<?php

namespace App\Http\Filters\Acl;

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
        // Resolve the role model from the route or query value
        $role = $this->request->route('role');

        if ($role instanceof Role) {
            // Role model was bound via route model binding
            $roleName = $role->name;
        } else {
            // Role slug or name passed as query parameter (?role=admin)
            $roleName = is_string($value) ? $value : null;
        }

        return $this->builder->whereHas('roles', function (Builder $query) use ($roleName) {
            $query->where('name', $roleName)
                ->orWhere('slug', $roleName)
                ->orWhere('id', $roleName);
        });
    }
}


