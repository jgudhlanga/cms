<?php

namespace App\Http\Resources\Students;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Institution\CourseSyllabusResource;

class StudentEnrolmentResource extends JsonResource
{

    public function toArray(Request $request): array
    {
       $classConfig = $this->academicCalendarStudentEnrolment?->academicCalendarClass?->classConfig;
        return [
            'type' => 'student-enrolment',
            'id' => $this->id,
            'attributes' => [
                'instituionDepartmentId' => $this->institution_department_id,
                'studentId' => $this->student_id,
                'studentProgramId' => $this->student_program_id,
                'departmentLevelId' => $this->department_level_id,
                'departmentCourseId' => $this->department_course_id,
                'modeOfStudyId' => $this->mode_of_study_id,
                'academicYearOptionId' => $this->academic_year_option_id,
                'academicCalendarId' => $this->academic_calendar_id,
                'studentEnrolmentStatusId' => $this->student_enrolment_status_id,
                'status' => $this->studentEnrolmentStatus?->name,
                'academicYearOption' => $this->academicYearOption?->name,
                'academicCalendar' => $this->academicCalendar?->calendar_year,
            ],
            'relationships' => [
                'details' => [
                    'academicCalendarStudentEnrolmentId' => $this->academicCalendarStudentEnrolment?->id,
                    'academicCalendarClassId' => $this->academicCalendarStudentEnrolment?->academic_calendar_class_id,
                    'classConfigId' => $classConfig?->id,
                    'syllabi' => $classConfig?->syllabus ? CourseSyllabusResource::collection($classConfig->syllabus) : null,
                ],
            ]
        ];
    }
}