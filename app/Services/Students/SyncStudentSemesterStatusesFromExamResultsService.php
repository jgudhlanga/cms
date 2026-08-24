<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\Students\StudentExamResultComment;
use App\Models\AcademicCalendars\Semester;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentSemester;
use Illuminate\Support\Collection;

class SyncStudentSemesterStatusesFromExamResultsService
{
    public function __construct(
        protected StudentEnrolmentProgressionService $progression,
        protected SyncStudentSemestersForEnrolmentService $syncStudentSemesters,
    ) {}

    /**
     * Apply exam-session comments onto student_semesters.
     * Phases without results become Unknown, except the calendar-current phase
     * becomes Active when the previous phase result is Proceed.
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

        $unknownStatusId = $this->ensureUnknownStatusId();
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

            if (! $studentSemester instanceof StudentSemester) {
                $examMetadata = $examMetadataBySlug->get($phaseSlug);
                $examComment = is_array($examMetadata) ? ($examMetadata['comment'] ?? null) : null;
                $previousStatusSlug = $examComment instanceof StudentExamResultComment
                    ? strtolower($examComment->value)
                    : $previousStatusSlug;

                continue;
            }

            $examMetadata = $examMetadataBySlug->get($phaseSlug);
            $examComment = is_array($examMetadata) ? ($examMetadata['comment'] ?? null) : null;

            if ($examComment instanceof StudentExamResultComment) {
                $statusSlug = strtolower($examComment->value);
                $statusId = $this->progression->statusIdBySlug($statusSlug);

                if ($statusId !== null && (int) $studentSemester->student_enrolment_status_id !== $statusId) {
                    $studentSemester->update(['student_enrolment_status_id' => $statusId]);
                    $changed = true;
                }

                $previousStatusSlug = $statusSlug;

                continue;
            }

            $isCurrent = $phaseSlug === $currentPhaseSlug;
            $shouldBeActive = $isCurrent
                && $previousStatusSlug === StudentEnrolmentProgressionService::STATUS_PROCEED
                && $activeStatusId !== null;

            $targetStatusId = $shouldBeActive ? $activeStatusId : $unknownStatusId;
            $targetSlug = $shouldBeActive
                ? StudentEnrolmentProgressionService::STATUS_ACTIVE
                : StudentEnrolmentProgressionService::STATUS_UNKNOWN;

            if ($targetStatusId !== null && (int) $studentSemester->student_enrolment_status_id !== $targetStatusId) {
                $studentSemester->update(['student_enrolment_status_id' => $targetStatusId]);
                $changed = true;
            }

            $previousStatusSlug = $targetSlug;
        }

        if ($changed) {
            $this->syncStudentSemesters->snapshotLatestPhaseOntoEnrolment($enrolment->fresh() ?? $enrolment);
        }
    }

    private function ensureUnknownStatusId(): ?int
    {
        $existing = $this->progression->statusIdBySlug(StudentEnrolmentProgressionService::STATUS_UNKNOWN);

        if ($existing !== null) {
            return $existing;
        }

        $status = StudentEnrolmentStatus::query()->firstOrCreate(
            ['name' => 'Unknown'],
            ['description' => 'Exam results for this semester/phase have not been recorded yet.'],
        );

        return (int) $status->id;
    }
}
