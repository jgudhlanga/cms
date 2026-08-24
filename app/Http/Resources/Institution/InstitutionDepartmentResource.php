<?php

namespace App\Http\Resources\Institution;

use App\Support\Institution\InstitutionDepartmentPresenter;
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
        $this->resource->loadMissing(['department', 'division.headOfDivision.user']);

        return [
            'type' => 'institution-department',
            'id' => $this->resource->id,
            'attributes' => [
                'departmentId' => $this->department_id,
                'departmentCode' => $this->resource->department_code,
                'department' => $this->department?->name,
                'divisionId' => $this->resource->division_id,
                'division' => $this->resource->division?->name,
                'headOfDivision' => $this->resource->division?->headOfDivision?->user?->full_name,
                'headOfDepartment' => InstitutionDepartmentPresenter::headOfDepartmentName($this->resource),
                'coursesOfferedCount' => (int) ($this->resource->department_courses_count ?? $this->resource->departmentCourses()->count()),
                'staffCount' => (int) ($this->resource->staff_count ?? $this->resource->staff()->count()),
                'levelsOffered' => InstitutionDepartmentPresenter::levelsOffered($this->resource),
                'colorCode' => $this->resource->color_code,
                'isAcademic' => $this->department?->is_academic,
                'description' => $this->resource->description,
                $this->mergeWhen($request->routeIs('institution-departments.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ],
        ];
    }
}
