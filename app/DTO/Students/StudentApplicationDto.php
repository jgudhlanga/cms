<?php

namespace App\DTO\Students;

use App\Http\Requests\Students\StudentApplicationRequest;
use App\Models\Students\Student;

readonly class StudentApplicationDto
{
    public function __construct(
        /** Programs */
        public int    $student_id,
        public int    $mode_of_study_id,
        public int    $institution_department_id,
        public int    $department_level_id,
        public int    $department_course_id,
        public int    $intake_period_id,
        public ?bool  $required_level_completed,
        public ?bool  $read_write_acknowledged,
    )
    {
    }

    public static function fromStudentApplicationRequest(StudentApplicationRequest $request, Student $student): StudentApplicationDto
    {
        return new self(
            student_id: $student->id,
            mode_of_study_id: $request->mode_of_study_id,
            institution_department_id: $request->department_id,
            department_level_id: $request->level_id,
            department_course_id: $request->course_id,
            intake_period_id: $request->intake_period_id,
            required_level_completed: $request->required_level_completed,
            read_write_acknowledged: $request->read_write_acknowledged,
        );
    }
}
