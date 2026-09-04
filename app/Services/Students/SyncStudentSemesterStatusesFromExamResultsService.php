<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\Students\StudentExamResultComment;
use App\Models\AcademicCalendars\Semester;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
use App\Services\Institution\ProgrammeSemesterResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SyncStudentSemesterStatusesFromExamResultsService
{
    public function __construct(
        protected StudentEnrolmentProgressionService $progression,
        protected SyncStudentSemestersForEnrolmentService $syncStudentSemesters,
        protected ProgrammeSemesterResolver $programmeSemesterResolver,
    ) {}

    /**
     * Apply exam-session comments onto student_semesters.
     * Only phases with an exam comment are updated. Manual overrides on phases
     * without results are left unchanged. When the previous phase result is
     * Proceed, the calendar-current phase without results is set to Active.
     *
     * @param  Collection<string, array{comment: StudentExamResultComment|null, session: string, candidateNumber: string}>  $examMetadataBySlug
     * @param  Collection<int, Semester>  $orderedPhases
     */
    public function sync(
        StudentEnrolment $enrolment,
        Collection $examMetadataBySlug,
        string $currentPhaseSlug,
        Collection $orderedPhases,
    ): void {
        $enrolment->loadMissing(['studentSemesters.semester', 'studentSemesters.studentEnrolmentStatus']);

        $activeStatusId = $this->progression->statusIdBySlug(StudentEnrolmentProgressionService::STATUS_ACTIVE);
        $semestersByPhaseId = $enrolment->studentSemesters->keyBy('semester_id');
        $changed = false;
        $previousStatusSlug = null;

        foreach ($orderedPhases as $phase) {
            if (! $phase instanceof Semester) {
                continue;
            }

            $phaseSlug = (string) $phase->slug;
            $studentSemester = $semestersByPhaseId->get((int) $phase->id);
            $examMetadata = $examMetadataBySlug->get($phaseSlug);
            $examComment = is_array($examMetadata) ? ($examMetadata['comment'] ?? null) : null;

            if (! $studentSemester instanceof StudentSemester) {
                $previousStatusSlug = $examComment instanceof StudentExamResultComment
                    ? strtolower($examComment->value)
                    : $previousStatusSlug;

                continue;
            }

            if ($examComment instanceof StudentExamResultComment) {
                $statusSlug = strtolower($examComment->value);

                if (! $this->awardBelongsOnPhase($statusSlug, $studentSemester)) {
                    $this->logAwardPhaseMismatch($enrolment, $studentSemester, $examMetadata, $phaseSlug);

                    continue;
                }

                $statusId = $this->progression->statusIdBySlug($statusSlug);

                if ($statusId !== null && (int) $studentSemester->student_enrolment_status_id !== $statusId) {
                    $studentSemester->update(['student_enrolment_status_id' => $statusId]);
                    $changed = true;
                }

                $previousStatusSlug = $statusSlug;

                continue;
            }

            $isCurrent = $phaseSlug === $currentPhaseSlug;
            $storedSlug = $this->progression->statusSlugForSemester($studentSemester);
            $shouldBeActive = $isCurrent
                && $previousStatusSlug === StudentEnrolmentProgressionService::STATUS_PROCEED
                && $activeStatusId !== null
                && (
                    $storedSlug === null
                    || $storedSlug === StudentEnrolmentProgressionService::STATUS_UNKNOWN
                );

            if ($shouldBeActive && (int) $studentSemester->student_enrolment_status_id !== $activeStatusId) {
                $studentSemester->update(['student_enrolment_status_id' => $activeStatusId]);
                $changed = true;
                $previousStatusSlug = StudentEnrolmentProgressionService::STATUS_ACTIVE;

                continue;
            }

            $previousStatusSlug = $storedSlug ?? $previousStatusSlug;
        }

        if ($changed) {
            $this->syncStudentSemesters->snapshotLatestPhaseOntoEnrolment($enrolment->fresh() ?? $enrolment);
        }
    }

    /**
     * A HEXCO AWARD is the verdict for a whole qualification, not for one semester. It only
     * belongs on the phase that completes the level. When it lands anywhere else the sitting
     * is for a different level than the one this enrolment is on — writing it would tick off
     * a phase the student has not actually done.
     */
    private function awardBelongsOnPhase(string $statusSlug, StudentSemester $studentSemester): bool
    {
        if ($statusSlug !== StudentEnrolmentProgressionService::STATUS_AWARD) {
            return true;
        }

        $studentSemester->loadMissing('enrolment');
        $enrolment = $studentSemester->enrolment;

        if (! $enrolment instanceof StudentEnrolment) {
            return true;
        }

        $dlc = $this->programmeSemesterResolver->resolveDepartmentLevelCourse($enrolment);

        if ($dlc !== null && $dlc->programmeSemesters->isNotEmpty()) {
            return $this->programmeSemesterResolver->isCompletionProgrammeSemester($studentSemester);
        }

        return $this->progression->isLastPhaseSemester($enrolment, $studentSemester);
    }

    /**
     * @param  array<string, mixed>|null  $examMetadata
     */
    private function logAwardPhaseMismatch(
        StudentEnrolment $enrolment,
        StudentSemester $studentSemester,
        mixed $examMetadata,
        string $phaseSlug,
    ): void {
        Log::warning('exam_results.award_phase_mismatch', [
            'student_id' => $enrolment->student_id,
            'student_enrolment_id' => $enrolment->id,
            'student_semester_id' => $studentSemester->id,
            'department_level_id' => $enrolment->department_level_id,
            'phase_slug' => $phaseSlug,
            'session' => is_array($examMetadata) ? ($examMetadata['session'] ?? null) : null,
        ]);
    }
}
