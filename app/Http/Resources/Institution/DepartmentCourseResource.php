<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentCourseResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'department-course',
            'id' => $this->resource->id,
            "attributes" => [
                "institutionDepartmentId" => $this->institution_department_id,
                "courseId" => $this->course_id,
                "course" => $this->course?->name,
                "showOnCurrentApplicationPeriod" => $this->show_on_current_application_period,
                'description' => $this->resource->description,
                $this->mergeWhen($request->routeIs('department-courses.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ],
            'relationships' => [
                'departmentCourseLevels' => DepartmentCourseLevelResource::collection($this->departmentCourseLevels),
            ]
        ];
    }
}
