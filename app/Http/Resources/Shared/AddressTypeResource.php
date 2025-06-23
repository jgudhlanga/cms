<?php

namespace App\Http\Resources\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressTypeResource extends JsonResource
{

	public function toArray(Request $request): array
	{
		return [
			'type' => 'address-type',
			'id' => $this->resource->id,
			"attributes" => [
				'title' => $this->resource->title,
				'description' => $this->resource->description,
				$this->mergeWhen($request->routeIs('address-types.*'), [
					'createdAt' => $this->resource->created_at,
					'updatedAt' => $this->resource->updated_at,
					'deletedAt' => $this->resource->deleted_at,
				]),
			],
		];
	}
}
