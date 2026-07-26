<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DivisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['headOfDivision.user']);

        return [
            'type' => 'division',
            'id' => $this->resource->id,
            'attributes' => [
                'name' => $this->resource->name,
                'position' => $this->resource->position,
                'description' => $this->resource->description,
                'headOfDivisionId' => $this->resource->head_of_division_id,
                'headOfDivision' => $this->resource->headOfDivision?->user?->full_name,
                $this->mergeWhen($request->routeIs('divisions.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ],
        ];
    }
}
