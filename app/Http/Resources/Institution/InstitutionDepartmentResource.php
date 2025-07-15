<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $department
 * @property mixed $department_id
 */
class InstitutionDepartmentResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'institution-department',
            'id' => $this->resource->id,
            "attributes" => [
                "departmentId" => $this->department_id,
                'department' => $this->department?->name,
                'isAcademic' => $this->department?->is_academic,
                'description' => $this->resource->description,
                $this->mergeWhen($request->routeIs('institution.departments.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ],

        ];
    }
}
