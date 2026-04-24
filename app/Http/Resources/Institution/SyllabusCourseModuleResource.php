<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SyllabusCourseModuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'syllabus-course-module',
            'id' => $this->resource->id,
            'attributes' => [
                'courseSyllabusId' => $this->resource->course_syllabus_id,
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
