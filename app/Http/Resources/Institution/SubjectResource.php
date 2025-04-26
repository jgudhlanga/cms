<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubjectResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'subjects',
            'id' => $this->resource->id,
            "attributes" => [
                'name' => $this->resource->name,
                $this->mergeWhen($request->routeIs('subjects.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ]
        ];
    }
}
