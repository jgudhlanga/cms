<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DivisionResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'division',
            'id' => $this->resource->id,
            "attributes" => [
                'name' => $this->resource->name,
                'position' => $this->resource->position,
                'description' => $this->resource->description,
                $this->mergeWhen($request->routeIs('divisions.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ]
        ];
    }
}
