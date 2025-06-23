<?php

namespace App\Http\Resources\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SponsorTypeResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'sponsor-type',
            'id' => $this->resource->id,
            "attributes" => [
                'name' => $this->resource->name,
                $this->mergeWhen($request->routeIs('sponsor-types.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ]
        ];
    }
}
