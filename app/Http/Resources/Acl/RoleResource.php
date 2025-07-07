<?php

namespace App\Http\Resources\Acl;

use App\Enums\Acl\PermissionEnum;
use App\Models\Acl\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{

	public function toArray(Request $request): array
	{
		$permissions = $this->getVisiblePermissions();
		return [
			"type" => "role",
			"id" => $this->resource->id,
			"attributes" => [
				'name' => $this->resource->name,
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
				])
			],
			'relationships' => [
				'permissions' => $request->routeIs('roles.show') ? PermissionResource::collection($permissions) : []
			],
		];
	}

	private function getVisiblePermissions()
	{
		if ($this->resource->permissions->contains('name', PermissionEnum::ROOT_MANAGE)) {
			return Permission::whereNotIn('name', [PermissionEnum::MANAGE_OWN_TENANT_DATA])->get();
		}

		return $this->resource->permissions;
	}
}
