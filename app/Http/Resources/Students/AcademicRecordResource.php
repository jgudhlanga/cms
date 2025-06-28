<?php

namespace App\Http\Resources\Students;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademicRecordResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'AcademicRecord',
            'id' => $this->id,
            'attributes' => [
                'studentId' => $this->student_id,
                'academicLevelId' => $this->academic_level_id,
                'academicLevel' => $this->academicLavel?->name ?? null,
                'school' => $this->school,
                'place' => $this->place,
                'fromLevel' => $this->from_level,
                'toLevel' => $this->to_level,
                'fromYear' => $this->from_year,
                'toYear' => $this->to_year,
                'studentUniqueNumber' => $this->student_unique_number,
                'examBoard' => $this->exam_board,
                'examMonth' => $this->exam_month,
                'examYear' => $this->exam_year,
                'examCenter' => $this->exam_center,
                'examResults' => $this->exam_results,
            ]
        ];
    }
}
