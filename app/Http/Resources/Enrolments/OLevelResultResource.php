<?php

namespace App\Http\Resources\Enrolments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OLevelResultResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'resultId' => $this->result_id,
            'subjectId' => $this->subject_id,
            'examYear' => $this->exam_year,
            'examSitting' => $this->exam_sitting,
            'gradeId' => $this->grade_id,
            'subject' => $this->subject,
            'grade' => $this->grade,
        ];
    }
}
