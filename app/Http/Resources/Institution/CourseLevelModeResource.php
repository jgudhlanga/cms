<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseLevelModeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'course-level-mode',
            'id' => $this->id,
            'attributes' => [
                'departmentLevelId' => $this->department_level_id,
                'departmentCourseId' => $this->department_course_id,
                'course' => $this->departmentCourse?->course?->name,
                'level' => $this->departmentLevel?->level?->name,
            ],
            'relationships' => [
                'modes' => ModeOfStudyResource::collection($this->mode_objects),
            ],
        ];
    }
}

