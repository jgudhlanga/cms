<?php

namespace App\Http\Resources\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationStepResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'application-step',
            'id' => $this->resource->id,
            "attributes" => [
                'name' => $this->resource->name,
                'description' => $this->resource->description,
                'position' => $this->resource->position,
                $this->mergeWhen($request->routeIs('application-steps.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ]
        ];
    }
}
