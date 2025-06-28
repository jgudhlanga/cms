<?php

namespace App\Http\Resources\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $id
 * @property mixed $title
 * @property mixed $description
 * @property mixed $created_at
 * @property mixed $updated_at
 * @property mixed $deleted_at
 */
class RaceResource extends JsonResource
{

	public function toArray(Request $request): array
	{
		return [
			'type' => 'race',
			'id' => $this->id,
			"attributes" => [
				'title' => $this->title,
				$this->mergeWhen($request->routeIs('races.*'), [
					'createdAt' => $this->created_at,
					'updatedAt' => $this->updated_at,
					'deletedAt' => $this->deleted_at,
				]),
			],
		];
	}
}
