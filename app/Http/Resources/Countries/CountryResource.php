<?php

namespace App\Http\Resources\Countries;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{

	public function toArray(Request $request): array
	{
		return [
			'type' => 'country',
			'id' => $this->resource->id,
			"attributes" => [
				'name' => $this->resource->name,
				$this->mergeWhen($request->routeIs('countries.*'), [
					'flag' => $this->resource->flag,
					'createdAt' => $this->resource->created_at,
					'updatedAt' => $this->resource->updated_at,
					'deletedAt' => $this->resource->deleted_at,
				]),
			]
		];
	}
}
