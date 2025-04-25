<?php

namespace App\Http\Resources\Acl;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		return [
			'type' => 'permission',
			'id' => $this->resource->id,
			'attributes' => [
				'name' => $this->resource->name,
				'description' => $this->resource->description,
				'guardName' => $this->resource->guard_name,
				$this->mergeWhen($request->routeIs('permissions.*'), [
					'createdAt' => $this->resource->created_at,
					'updatedAt' => $this->resource->updated_at,
					'deletedAt' => $this->resource->deleted_at,
				])
			],
			'relationships' => [
				'module' => ModuleResource::make($this->resource->module),
			],
		];
	}
}
