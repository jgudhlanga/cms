<?php

namespace App\DTO\Students;

use App\Http\Requests\Students\AcademicRecordRequest;
use App\Models\Students\Student;

readonly class AcademicRecordDto
{
    public function __construct(
        /** Programs */
        public int     $student_id,
        public int     $academic_level_id,
        public string  $school,
        public string  $place,
        public ?int    $from_level,
        public ?int    $to_level,
        public ?string $from_year,
        public ?string $to_year,
        public ?string $student_unique_number,
        public ?string $exam_board,
        public ?string $exam_month,
        public ?string $exam_year,
        public ?string $exam_center,
        public ?array  $exam_results,
    )
    {
    }

    public static function fromAcademicRecordRequest(AcademicRecordRequest $request, Student $student): AcademicRecordDto
    {
        return new self(
            student_id: $student->id,
            academic_level_id: $request->academic_level_id,
            school: $request->school,
            place: $request->place,
            from_level: $request->from_level,
            to_level: $request->to_level,
            from_year: $request->from_year,
            to_year: $request->to_year,
            student_unique_number: $request->student_unique_number,
            exam_board: $request->exam_board,
            exam_month: $request->exam_month,
            exam_year: $request->exam_year,
            exam_center: $request->exam_center,
            exam_results: $request->exam_results,
        );
    }
}
