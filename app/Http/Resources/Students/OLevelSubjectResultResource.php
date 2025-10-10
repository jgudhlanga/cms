<?php

namespace App\Http\Resources\Students;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class OLevelSubjectResultResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'type' => 'o-level-subject-results',
            'id' => $this->subject_id,
            "attributes" => [
                'studentId' => $this->student_id,
                'resultId' => $this->result_id,
                'subject' => $this->subject ??null,
                'examYear' => $this->exam_year,
                'examSitting' => $this->exam_sitting,
                'gradeId' => $this->grade_id,
                'grade' => $this->grade ?? null,
            ]
        ];
    }
}
