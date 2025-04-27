<?php

namespace App\Http\Resources\Titles;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TitleResource extends JsonResource
{

	public function toArray(Request $request): array
	{
		return [
			'type' => 'title',
			'id' => $this->resource->id,
			"attributes" => [
				'name' => $this->resource->name,
				$this->mergeWhen($request->routeIs('titles.*'), [
					'createdAt' => $this->resource->created_at,
					'updatedAt' => $this->resource->updated_at,
					'deletedAt' => $this->resource->deleted_at,
				]),
			]
		];
	}
}
