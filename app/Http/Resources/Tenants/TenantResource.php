<?php

namespace App\Http\Resources\Tenants;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $id
 * @property mixed $name
 * @property mixed $created_at
 * @property mixed $updated_at
 * @property mixed $deleted_at
 */
class TenantResource extends JsonResource
{

	public function toArray(Request $request): array
	{
		return [
			'type' => 'tenant',
			'id' => $this->id,
			"attributes" => [
				'name' => $this->name,
				$this->mergeWhen($request->routeIs('tenants.*'), [
					'createdAt' => $this->created_at,
					'updatedAt' => $this->updated_at,
					'deletedAt' => $this->deleted_at,
				]),
			]
		];
	}
}
