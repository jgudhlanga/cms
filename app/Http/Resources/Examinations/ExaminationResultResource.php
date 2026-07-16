<?php

namespace App\Http\Resources\Examinations;

use App\Models\Examinations\ExaminationResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ExaminationResult
 */
class ExaminationResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'discipline' => $this->discipline,
            'courseCode' => $this->course_code,
            'candidateNumber' => $this->candidate_number,
            'surname' => $this->surname,
            'firstNames' => $this->first_names,
            'subjectCode' => $this->subject_code,
            'subject' => $this->subject,
            'grade' => $this->grade,
            'session' => $this->session,
            'courseComment' => $this->course_comment,
        ];
    }
}
