<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseModeResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'course-mode',
            'id' => $this->id,
            'attributes' => [
                'departmentCourseId' => $this?->department_course_id,
                'modeOfStudyId' => $this?->mode_of_study_id,
                'modeOfStudy' => $this?->modeOfStudy->name,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at
            ]
        ];
    }
}
