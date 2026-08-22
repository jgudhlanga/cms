<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Services\Students\ResolveStudentEnrolmentAttributesService;
use App\Services\Students\StudentEnrolmentProgressionService;
use App\Services\Students\SyncStudentSemestersForEnrolmentService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class UpsertYearStudentEnrolmentAction
{
    public function __construct(
        protected ResolveStudentEnrolmentAttributesService $resolveStudentEnrolmentAttributes,
        protected SyncStudentSemestersForEnrolmentService $syncStudentSemesters,
        protected StudentEnrolmentProgressionService $progression,
    ) {}

    public function execute(StudentApplication $studentApplication, ?CarbonInterface $asOf = null): StudentEnrolment
    {
        $studentApplication->loadMissing([
            'student',
            'institutionDepartment',
            'departmentLevel',
            'departmentCourse',
        ]);

        $attributes = $this->resolveStudentEnrolmentAttributes->resolve(
            (int) $studentApplication->student_id,
            (int) $studentApplication->id,
            $asOf,
        );

        $calendarYear = AcademicCalendar::query()
            ->whereKey($attributes['academic_calendar_id'])
            ->value('calendar_year');

        return DB::transaction(function () use ($studentApplication, $attributes, $calendarYear, $asOf): StudentEnrolment {
            $existing = $this->findExistingYearEnrolment(
                (int) $studentApplication->id,
                is_string($calendarYear) ? $calendarYear : null,
                (int) $studentApplication->mode_of_study_id,
            );

            if ($existing instanceof StudentEnrolment) {
                $enrolment = $existing;
            } else {
                $enrolment = StudentEnrolment::query()->create([
                    'student_id' => $studentApplication->student_id,
                    'student_application_id' => $studentApplication->id,
                    'institution_department_id' => $studentApplication->institution_department_id,
                    'department_level_id' => $studentApplication->department_level_id,
                    'department_course_id' => $studentApplication->department_course_id,
                    'semester_id' => $attributes['semester_id'],
                    'academic_calendar_id' => $attributes['academic_calendar_id'],
                    'mode_of_study_id' => $studentApplication->mode_of_study_id,
                    'student_enrolment_status_id' => $attributes['student_enrolment_status_id'],
                ]);
            }

            $this->syncStudentSemesters->sync($enrolment->fresh() ?? $enrolment, $asOf, [
                'sourceSemesterId' => $attributes['semester_id'],
                'sourceStatusId' => $attributes['student_enrolment_status_id'],
                'snapshotEnrolment' => true,
            ]);

            $this->progression->pinSyllabusFromMatchingClassConfig($enrolment->fresh() ?? $enrolment);

            return $enrolment->fresh(['studentSemesters.semester', 'studentEnrolmentStatus']) ?? $enrolment;
        });
    }

    private function findExistingYearEnrolment(
        int $studentApplicationId,
        ?string $calendarYear,
        int $modeOfStudyId,
    ): ?StudentEnrolment {
        if ($calendarYear === null || $calendarYear === '') {
            return null;
        }

        return StudentEnrolment::query()
            ->where('student_application_id', $studentApplicationId)
            ->where('mode_of_study_id', $modeOfStudyId)
            ->whereNull('deleted_at')
            ->whereHas('academicCalendar', fn ($query) => $query->where('calendar_year', $calendarYear))
            ->orderByDesc('id')
            ->first();
    }
}
