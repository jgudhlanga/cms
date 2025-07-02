<?php

namespace App\Http\Resources\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmploymentTypeResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'employment-type',
            'id' => $this->resource->id,
            "attributes" => [
                'name' => $this->resource->name,
                'description' => $this->resource->description,
                $this->mergeWhen($request->routeIs('employment-types.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ]
        ];
    }
}
