<?php

namespace App\Http\Resources\Enrolments;

use App\Http\Resources\Institution\DepartmentApplicationStepResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrolmentResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'enrolments',
            'id' => $this->id,
            'attributes' => [
                'studentId' => $this?->student?->id,
                'studentName' => $this->student?->user?->full_name,
                'studentNumber' => $this->student?->student_number,
                'institutionDepartmentId' => $this->institution_department_id,
                'department' => $this->institutionDepartment?->department?->name,
                'departmentLevelId' => $this->department_level_id,
                'level' => $this->departmentLevel?->level?->name,
                'departmentCourseId' => $this->department_course_id,
                'course' => $this->departmentCourse?->course?->name,
                'applicationTrackingNumber' => $this->application_tracking_number,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
                'deletedAt' => $this->deleted_at,
            ],
            'relationships' => [
                'departmentWorkflowStep' => DepartmentApplicationStepResource::make($this->departmentWorkflowStep)
            ]
        ];
    }
}
