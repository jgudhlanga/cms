<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'grade',
            'id' => $this->resource->id,
            "attributes" => [
                'name' => $this->resource->name,
                $this->mergeWhen($request->routeIs('grades.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ]
        ];
    }
}
