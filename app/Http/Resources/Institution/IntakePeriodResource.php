<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntakePeriodResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'intake-period',
            'id' => $this->resource->id,
            "attributes" => [
                'name' => $this->resource->name,
                'startDate' => $this->resource->start_date,
                'endDate' => $this->resource->end_date,
                'isActive' => $this->resource->is_active,
                'description' => $this->resource->description,
                $this->mergeWhen($request->routeIs('intake-periods.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ]
        ];
    }
}
