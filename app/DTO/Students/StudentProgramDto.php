<?php

namespace App\DTO\Students;

use App\Http\Requests\Students\StudentProgramRequest;
use App\Models\Students\Student;

readonly class StudentProgramDto
{
    public function __construct(
        /** Programs */
        public int    $student_id,
        public int    $department_id,
        public int    $level_id,
        public int    $course_id,
        public ?array $o_level_subjects,
        public ?bool  $required_level_completed,
        public ?bool  $read_write_acknowledged,
    )
    {
    }

    public static function fromStudentProgramRequest(StudentProgramRequest $request, Student $student): StudentProgramDto
    {
        return new self(
            student_id: $student->id,
            department_id: $request->department_id,
            level_id: $request->level_id,
            course_id: $request->course_id,
            o_level_subjects: $request->o_level_subject_ids,
            required_level_completed: $request->required_level_completed,
            read_write_acknowledged: $request->read_write_acknowledged,
        );
    }
}
