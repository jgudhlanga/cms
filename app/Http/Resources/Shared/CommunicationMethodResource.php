<?php

namespace App\Http\Resources\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunicationMethodResource extends JsonResource
{

	public function toArray(Request $request): array
	{
		return [
			'type' => 'communication-method',
			'id' => $this->resource->id,
			"attributes" => [
				'title' => $this->resource->title,
				$this->mergeWhen($request->routeIs('communication-methods.*'), [
					'createdAt' => $this->resource->created_at,
					'updatedAt' => $this->resource->updated_at,
					'deletedAt' => $this->resource->deleted_at,
				]),
			]
		];
	}
}
