<?php

namespace App\Http\Resources\Rbac;

use App\Models\Rbac\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $permissions = $this->getVisiblePermissions();

        return [
            'type' => 'role',
            'id' => $this->resource->id,
            'attributes' => [
                'name' => $this->resource->name,
                'slug' => $this->resource->slug,
                'guardName' => $this->resource->guard_name,
                'roleGroupId' => $this->role_group_id,
                'roleGroup' => $this->roleGroup?->name,
                'description' => $this->resource->description,
                $this->mergeWhen($request->routeIs('roles.*'), [
                    'permissionsCount' => $permissions->count(),
                    'usersCount' => $this->resource->users->count(),
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ],
            'relationships' => [
                $this->mergeWhen($request->routeIs('roles.show'), [
                    'permissions' => PermissionResource::collection($permissions),
                ]),

            ],
        ];
    }

    private function getVisiblePermissions()
    {
        if ($this->resource->permissions->contains('name', 'root:manage')) {
            return Permission::whereNotIn('name', ['manageOwnData:tenants'])->get();
        }

        return $this->resource->permissions;
    }
}
