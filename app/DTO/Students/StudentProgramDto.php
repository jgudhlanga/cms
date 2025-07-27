<?php

namespace App\DTO\Students;

use App\Http\Requests\Students\StudentProgramRequest;
use App\Models\Students\Student;

readonly class StudentProgramDto
{
    public function __construct(
        /** Programs */
        public int    $student_id,
        public int    $institution_department_id,
        public int    $department_level_id,
        public int    $department_course_id,
        public int    $intake_period_id,
        public ?bool  $required_level_completed,
        public ?bool  $read_write_acknowledged,
    )
    {
    }

    public static function fromStudentProgramRequest(StudentProgramRequest $request, Student $student): StudentProgramDto
    {
        return new self(
            student_id: $student->id,
            institution_department_id: $request->department_id,
            department_level_id: $request->level_id,
            department_course_id: $request->course_id,
            intake_period_id: $request->intake_period_id,
            required_level_completed: $request->required_level_completed,
            read_write_acknowledged: $request->read_write_acknowledged,
        );
    }
}
