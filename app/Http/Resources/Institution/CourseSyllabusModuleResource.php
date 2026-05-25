<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseSyllabusModuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'course-syllabus-module',
            'id' => $this->resource->id,
            'attributes' => [
                'courseSyllabusId' => $this->resource->course_syllabus_id,
                'academicYearOptionId' => $this->resource->academic_year_option_id,
                'academicYearOptionName' => $this->resource->academicYearOption?->name,
                'title' => $this->resource->title,
                'code' => $this->resource->code,
                'durationInHours' => $this->resource->duration_in_hours,
                'nqlLevel' => $this->resource->nql_level,
                'prerequisiteModuleIds' => $this->resource->prerequisite_module_ids ?? [],
                'shared' => (bool) $this->resource->shared,
                'createdAt' => $this->resource->created_at,
                'updatedAt' => $this->resource->updated_at,
                'deletedAt' => $this->resource->deleted_at,
            ],
        ];
    }
}
