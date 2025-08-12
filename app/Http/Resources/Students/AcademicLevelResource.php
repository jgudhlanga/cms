<?php

namespace App\Http\Resources\Students;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class AcademicLevelResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'academic-level-results',
            'id' => $this->id,
            "attributes" => [
                'academicLevelId' => $this->academic_level_id,
                'academicLevel' => $this->academicLevel?->name ?? null,
                'subjectId' => $this->subject_id,
                'subject' => $this->subject?->name ?? null,
                'examYear' => $this->exam_year,
                'examSitting' => $this->exam_sitting,
                'gradeId' => $this->grade_id,
                'grade' => $this->grade?->name ?? null,
                'remarks' => $this->remarks,
                'createdAt' => Carbon::parse($this->created_at)->format('Y-m-d'),
                'updatedAt' => Carbon::parse($this->updated_at)->format('Y-m-d'),
                'deletedAt' => $this->deleted_at ? Carbon::parse($this->deleted_at)->format('Y-m-d') : null,
            ]
        ];
    }
}
