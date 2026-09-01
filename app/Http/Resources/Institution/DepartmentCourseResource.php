<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $courseModes
 */
class DepartmentCourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing([
            'course',
            'departmentCourseLevels.departmentLevel.level',
            'departmentCourseLevels.programmeSemesters',
        ]);

        return [
            'type' => 'department-course',
            'id' => $this->resource->id,
            'attributes' => [
                'institutionDepartmentId' => $this->institution_department_id,
                'courseId' => $this->course_id,
                'course' => $this->course?->name,
                'courseworkCaptureEnabled' => $this->coursework_capture_enabled ?? true,
                'hasEnrolmentRequirements' => $this->course?->has_enrolment_requirements,
                'description' => $this->resource->description,
                $this->mergeWhen($request->routeIs('department-courses.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ],
            'relationships' => [
                'departmentCourseLevels' => $this->departmentCourseLevels ? DepartmentLevelCourseResource::collection($this->departmentCourseLevels) : null,
            ],
        ];
    }
}
