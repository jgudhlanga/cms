<?php

namespace App\Http\Resources\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademicLevelResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'academic-level',
            'id' => $this->resource->id,
            "attributes" => [
                'name' => $this->resource->name,
                'position' => $this->resource->position,
                'description' => $this->resource->description,
                $this->mergeWhen($request->routeIs('academic-levels.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ]
        ];
    }
}
