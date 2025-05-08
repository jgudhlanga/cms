<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentLevelResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'department-level',
            'id' => $this->resource->id,
            "attributes" => [
                "institutionDepartmentId" => $this->institution_department_id,
                "levelId" => $this->level_id,
                "level" => $this->level?->name,
                'description' => $this->resource->description,
                $this->mergeWhen($request->routeIs('department-levels.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ],),
            ]
        ];
    }
}
