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
        $this->resource->loadMissing(['department', 'division']);

        return [
            'type' => 'institution-department',
            'id' => $this->resource->id,
            'attributes' => [
                'departmentId' => $this->department_id,
                'departmentCode' => $this->resource->department_code,
                'department' => $this->department?->name,
                'divisionId' => $this->resource->division_id,
                'division' => $this->resource->division?->name,
                'isAcademic' => $this->department?->is_academic,
                'hasApprenticeCourses' => (bool) $this->resource->has_apprentice_courses,
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
