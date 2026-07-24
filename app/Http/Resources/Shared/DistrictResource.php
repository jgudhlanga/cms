<?php

namespace App\Http\Resources\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DistrictResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('province');

        return [
            'type' => 'district',
            'id' => $this->resource->id,
            "attributes" => [
                'name' => $this->resource->name,
                "provinceId" => $this->province_id,
                "province" => $this->province?->title,
                'description' => $this->resource->description,
                $this->mergeWhen($request->routeIs('districts.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ]
        ];
    }
}
