<?php

declare(strict_types=1);

namespace App\Http\Resources\Enrolments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrolmentApplicantLookupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'applicationId' => $this->id,
            'studentName' => $this->student?->user?->full_name,
            'department' => $this->institutionDepartment?->department?->name,
            'level' => $this->departmentLevel?->level?->name,
            'course' => $this->departmentCourse?->course?->name,
            'applicationTrackingNumber' => $this->application_tracking_number,
            'classListType' => (string) data_get($this->resource, 'class_list_type'),
            'institutionDepartmentId' => $this->institution_department_id,
            'departmentLevelId' => $this->department_level_id,
            'departmentCourseId' => $this->department_course_id,
            'modeOfStudyId' => $this->mode_of_study_id,
        ];
    }
}
