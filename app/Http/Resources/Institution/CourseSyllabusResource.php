<?php

namespace App\Http\Resources\Institution;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseSyllabusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'course-syllabus',
            'id' => $this->resource->id,
            'attributes' => [
                'institutionDepartmentId' => $this->resource->institution_department_id,
                'departmentLevelCourseId' => $this->resource->department_level_course_id,
                'level' => $this->resource->departmentLevelCourse?->departmentLevel?->level?->name ?? '---',
                'course' => $this->resource->departmentLevelCourse?->departmentCourse?->course?->name ?? '---',
                'title' => $this->resource->title,
                'code' => $this->resource->code,
                'implementationYear' => $this->resource->implementation_year,
                'status' => $this->resource->status?->value,
                'syllabusDocumentId' => $this->resource->syllabus_document_id,
                'syllabusDocumentUrl' => $this->resource->syllabus_document_url,
                'syllabusDocumentDownloadUrl' => $this->resource->syllabus_document_id
                    ? route('department-course-syllabuses.syllabus', [
                        'institution_department' => $this->resource->institution_department_id,
                        'course_syllabus' => $this->resource->id,
                    ])
                    : null,
                $this->mergeWhen(true, [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ],
        ];
    }
}
