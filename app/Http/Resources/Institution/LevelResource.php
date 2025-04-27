<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LevelResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'level',
            'id' => $this->resource->id,
            "attributes" => [
                'name' => $this->resource->name,
                'description' => $this->resource->description,
                $this->mergeWhen($request->routeIs('levels.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ]
        ];
    }
}
