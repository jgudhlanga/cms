<?php

namespace App\Http\Resources\Students;

use App\Http\Resources\Institution\CourseSyllabusResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentEnrolmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing([
            'studentEnrolmentStatus',
            'semester',
            'academicCalendar',
            'studentSemesters.semester',
            'studentSemesters.studentEnrolmentStatus',
            'academicCalendarStudentEnrolment.academicCalendarClass.classConfig.syllabus',
        ]);

        $classConfig = $this->academicCalendarStudentEnrolment?->academicCalendarClass?->classConfig;
        $currentSemester = $this->currentStudentSemester();

        return [
            'type' => 'student-enrolment',
            'id' => $this->id,
            'attributes' => [
                'instituionDepartmentId' => $this->institution_department_id,
                'studentId' => $this->student_id,
                'studentApplicationId' => $this->student_application_id,
                'departmentLevelId' => $this->department_level_id,
                'departmentCourseId' => $this->department_course_id,
                'modeOfStudyId' => $this->mode_of_study_id,
                'semesterId' => $currentSemester?->semester_id ?? $this->semester_id,
                'academicCalendarId' => $this->academic_calendar_id,
                'studentEnrolmentStatusId' => $currentSemester?->student_enrolment_status_id ?? $this->student_enrolment_status_id,
                'status' => $currentSemester?->studentEnrolmentStatus?->name ?? $this->studentEnrolmentStatus?->name,
                'semester' => $currentSemester?->semester?->name ?? $this->semester?->name,
                'academicCalendar' => $this->academicCalendar?->calendar_year,
            ],
            'relationships' => [
                'details' => [
                    'academicCalendarStudentEnrolmentId' => $this->academicCalendarStudentEnrolment?->id,
                    'academicCalendarClassId' => $this->academicCalendarStudentEnrolment?->academic_calendar_class_id,
                    'classConfigId' => $classConfig?->id,
                    'syllabi' => $classConfig?->syllabus ? CourseSyllabusResource::collection($classConfig->syllabus) : null,
                ],
                'semesters' => $this->studentSemesters
                    ->sortBy(fn ($row) => (int) ($row->semester?->id ?? 0))
                    ->values()
                    ->map(fn ($row): array => [
                        'id' => $row->id,
                        'semesterId' => $row->semester_id,
                        'semester' => $row->semester?->name,
                        'studentEnrolmentStatusId' => $row->student_enrolment_status_id,
                        'status' => $row->studentEnrolmentStatus?->name,
                        'courseSyllabusIds' => $row->course_syllabus_ids ?? [],
                    ])
                    ->all(),
            ],
        ];
    }
}
