<?php

namespace App\Http\Resources\Institution;

use App\Models\Institution\Level;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $institution_department_id
 */
class CourseRequirementResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $level = Level::where('id', $this->required_level_id)->first();
        return [
            'type' => 'level-course-requirement',
            'id' => $this->resource->id,
            "attributes" => [
                "departmentLeveId" => $this->department_level_id,
                "departmentCourseId" => $this->department_course_id,
                "isOLevelRequired" => $this->is_o_level_required,
                "requiredSubjectsCount" => $this->required_subjects_count,
                "mainSubjectsCount" => $this->main_subjects_count,
                "mainSubjectIds" => $this->main_subject_ids,
                "otherSubjectsCount" => $this->other_subjects_count,
                "onlyReadWriteRequired" => $this->only_read_write_required,
                "requiredLevelId" => $this->required_level_id,
                "requiredLevel" => $level?->name,
            ],
            "relationships" => [
                'subjects' => SubjectResource::collection($this->main_subjects),
            ],
        ];
    }
}
