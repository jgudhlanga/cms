<?php

namespace App\Http\Resources\AcademicCalendars;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademicYearOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'academic-year-option',
            'id' => $this->resource->id,
            'attributes' => [
                'name' => $this->resource->name,
                'description' => $this->resource->description,
                $this->mergeWhen($request->routeIs('academic-year-options.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ],
        ];
    }
}
