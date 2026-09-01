<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Exceptions\Students\StudentEnrolmentProgressionException;
use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
use App\Services\Institution\ProgrammeSemesterResolver;
use App\Services\Students\ResolveStudentEnrolmentAttributesService;
use App\Services\Students\StudentEnrolmentProgressionService;
use App\Services\Students\SyncStudentSemestersForEnrolmentService;
use Illuminate\Support\Facades\DB;

class AdvanceToNextSemesterAction
{
    public function __construct(
        protected ResolveStudentEnrolmentAttributesService $resolveStudentEnrolmentAttributes,
        protected StudentEnrolmentProgressionService $progression,
        protected UpsertYearStudentEnrolmentAction $upsertYearStudentEnrolment,
        protected SyncStudentSemestersForEnrolmentService $syncStudentSemesters,
        protected ProgrammeSemesterResolver $programmeSemesterResolver,
    ) {}

    public function execute(StudentEnrolment $enrolment): StudentEnrolment
    {
        $enrolment->loadMissing([
            'studentApplication',
            'studentEnrolmentStatus',
            'departmentLevel.level',
            'academicCalendar',
            'studentSemesters.semester',
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

            $nextPhase = $this->progression->nextPhaseSemester($enrolment);

            if (! $nextPhase instanceof Semester) {
                throw new StudentEnrolmentProgressionException(
                    __('students.enrolment_cannot_advance_phase'),
                );
            }

            if ((int) $attributes['academic_calendar_id'] !== (int) $enrolment->academic_calendar_id) {
                return $this->upsertYearStudentEnrolment->execute($studentApplication);
            }

            $currentSemester = $this->progression->currentStudentSemester($enrolment);
            $nextProgrammeSemester = $this->progression->nextProgrammeSemester($currentSemester);
            $proceedStatusId = $this->progression->statusIdBySlug(StudentEnrolmentProgressionService::STATUS_PROCEED);
            $activeStatusId = $attributes['student_enrolment_status_id'];

            if ($currentSemester instanceof StudentSemester && $proceedStatusId !== null) {
                $this->progression->updateStudentSemesterStatus($currentSemester, $proceedStatusId);
            }

            $createAttributes = [
                'student_enrolment_status_id' => $activeStatusId,
            ];

            if ($nextProgrammeSemester instanceof ProgrammeSemester) {
                $createAttributes['programme_semester_id'] = $nextProgrammeSemester->id;
                $dlc = $this->programmeSemesterResolver->resolveDepartmentLevelCourse($enrolment);

                if ($dlc !== null) {
                    $globalSemester = $this->programmeSemesterResolver->globalSemesterForProgrammeSemester($dlc, $nextProgrammeSemester);

                    if ($globalSemester !== null) {
                        $createAttributes['semester_id'] = $globalSemester->id;
                    }
                }
            }

            StudentSemester::query()->updateOrCreate(
                array_filter([
                    'student_enrolment_id' => $enrolment->id,
                    'semester_id' => $createAttributes['semester_id'] ?? $nextPhase->id,
                    'programme_semester_id' => $createAttributes['programme_semester_id'] ?? null,
                ], fn ($value) => $value !== null),
                $createAttributes,
            );

            $this->syncStudentSemesters->snapshotLatestPhaseOntoEnrolment($enrolment->fresh() ?? $enrolment);
            $this->progression->pinSyllabusFromMatchingClassConfig($enrolment->fresh() ?? $enrolment);

            return $enrolment->fresh(['studentSemesters.semester', 'studentEnrolmentStatus']) ?? $enrolment;
        });
    }
}
