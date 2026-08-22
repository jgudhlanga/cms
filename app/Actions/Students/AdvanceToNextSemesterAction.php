<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Exceptions\Students\StudentEnrolmentProgressionException;
use App\Models\AcademicCalendars\Semester;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
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
            $proceedStatusId = $this->progression->statusIdBySlug(StudentEnrolmentProgressionService::STATUS_PROCEED);
            $activeStatusId = $attributes['student_enrolment_status_id'];

            if ($currentSemester instanceof StudentSemester && $proceedStatusId !== null) {
                $this->progression->updateStudentSemesterStatus($currentSemester, $proceedStatusId);
            }

            StudentSemester::query()->updateOrCreate(
                [
                    'student_enrolment_id' => $enrolment->id,
                    'semester_id' => $nextPhase->id,
                ],
                [
                    'student_enrolment_status_id' => $activeStatusId,
                ],
            );

            $this->syncStudentSemesters->snapshotLatestPhaseOntoEnrolment($enrolment->fresh() ?? $enrolment);
            $this->progression->pinSyllabusFromMatchingClassConfig($enrolment->fresh() ?? $enrolment);

            return $enrolment->fresh(['studentSemesters.semester', 'studentEnrolmentStatus']) ?? $enrolment;
        });
    }
}
