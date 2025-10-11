<?php

namespace App\DTO\Students;

use App\Http\Requests\Students\OLevelResultRequest;

readonly class OLevelResultDto
{
    public function __construct(
        public int    $subject_id,
        public string $exam_year,
        public string $exam_sitting,
        public int    $grade_id,
    )
    {
    }

    public static function fromOLevelResultRequest(OLevelResultRequest $request): OLevelResultDto
    {
        return new self(
            subject_id: $request->subject_id,
            exam_year: $request->exam_year,
            exam_sitting: $request->exam_sitting,
            grade_id: $request->grade_id,
        );
    }
}
