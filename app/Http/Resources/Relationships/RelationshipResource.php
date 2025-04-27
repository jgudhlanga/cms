<?php

namespace App\Http\Resources\Relationships;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RelationshipResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'relationship',
            'id' => $this->resource->id,
            "attributes" => [
                'name' => $this->resource->name,
                $this->mergeWhen($request->routeIs('relationships.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ]
        ];
    }
}
