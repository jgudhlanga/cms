<?php

namespace App\Http\Resources\Genders;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GenderResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		return [
			'type' => 'gender',
			'id' => $this->resource->id,
			'attributes' => [
				'title' => $this->resource->title,
				$this->mergeWhen($request->routeIs('genders.*'), [
					'createdAt' => $this->resource->created_at,
					'updatedAt' => $this->resource->updated_at,
					'deletedAt' => $this->resource->deleted_at,
				]),
			]
		];
	}
}
