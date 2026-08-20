<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Exceptions\Students\StudentEnrolmentProgressionException;
use App\Models\Students\StudentEnrolment;
use App\Services\Students\ResolveStudentEnrolmentAttributesService;
use App\Services\Students\StudentEnrolmentProgressionService;
use Illuminate\Support\Facades\DB;

class AdvanceToNextSemesterAction
{
    public function __construct(
        protected ResolveStudentEnrolmentAttributesService $resolveStudentEnrolmentAttributes,
        protected StudentEnrolmentProgressionService $progression,
    ) {}

    public function execute(StudentEnrolment $enrolment): StudentEnrolment
    {
        $enrolment->loadMissing([
            'studentApplication',
            'studentEnrolmentStatus',
            'departmentLevel.level',
            'academicCalendar',
        ]);

        if (! $this->progression->canAdvanceToNextPhase($enrolment)) {
            throw new StudentEnrolmentProgressionException(
                __('students.enrolment_cannot_advance_phase'),
            );
        }

        $studentApplication = $enrolment->studentApplication;

        if ($studentApplication === null) {
            throw new StudentEnrolmentProgressionException(
                __('students.enrolment_cannot_advance_phase'),
            );
        }

        return DB::transaction(function () use ($enrolment, $studentApplication): StudentEnrolment {
            $attributes = $this->resolveStudentEnrolmentAttributes->resolve(
                (int) $enrolment->student_id,
                (int) $studentApplication->id,
            );

            if ((int) $attributes['semester_id'] === (int) $enrolment->semester_id) {
                throw new StudentEnrolmentProgressionException(
                    __('students.enrolment_cannot_advance_phase'),
                );
            }

            $next = StudentEnrolment::query()->updateOrCreate(
                [
                    'student_id' => $enrolment->student_id,
                    'student_application_id' => $studentApplication->id,
                    'institution_department_id' => $enrolment->institution_department_id,
                    'department_level_id' => $enrolment->department_level_id,
                    'department_course_id' => $enrolment->department_course_id,
                    'semester_id' => $attributes['semester_id'],
                    'academic_calendar_id' => $attributes['academic_calendar_id'],
                    'mode_of_study_id' => $enrolment->mode_of_study_id,
                ],
                [
                    'student_enrolment_status_id' => $attributes['student_enrolment_status_id'],
                ],
            );

            $this->progression->pinSyllabusFromMatchingClassConfig($next);

            return $next->fresh() ?? $next;
        });
    }
}
