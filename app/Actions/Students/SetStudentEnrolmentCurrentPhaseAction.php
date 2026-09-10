<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
use App\Services\Institution\ProgrammeSemesterResolver;
use App\Services\Students\StudentEnrolmentProgressionService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SetStudentEnrolmentCurrentPhaseAction
{
    public function __construct(
        protected ProgrammeSemesterResolver $programmeSemesterResolver,
        protected StudentEnrolmentProgressionService $progression,
    ) {}

    public function execute(StudentEnrolment $enrolment, ProgrammeSemester $programmeSemester): StudentEnrolment
    {
        $enrolment->loadMissing([
            'studentSemesters.semester',
            'studentSemesters.programmeSemester',
            'departmentLevel.level',
            'departmentCourse',
            'studentEnrolmentStatus',
        ]);

        $dlc = $this->programmeSemesterResolver->resolveDepartmentLevelCourse($enrolment);

        if ($dlc === null) {
            throw new InvalidArgumentException(__('trans.department_semester_reconciliation_missing_offering'));
        }

        if ((int) $programmeSemester->department_level_course_id !== (int) $dlc->id) {
            throw new InvalidArgumentException(__('trans.department_semester_reconciliation_phase_wrong_offering'));
        }

        $globalSemester = $this->programmeSemesterResolver->calendarSemesterForClassConfig($dlc, $programmeSemester);

        if (! $globalSemester instanceof Semester && $programmeSemester->isTaught()) {
            throw new InvalidArgumentException(__('trans.department_semester_reconciliation_phase_unmapped'));
        }

        $this->guardAgainstDuplicatePhasePin($enrolment, $programmeSemester, $globalSemester);

        $activeStatusId = $this->resolveTargetStatusId($enrolment);

        return DB::transaction(function () use (
            $enrolment,
            $programmeSemester,
            $globalSemester,
            $activeStatusId,
        ): StudentEnrolment {
            $studentSemester = null;

            if ($globalSemester instanceof Semester) {
                $studentSemester = $enrolment->studentSemesters
                    ->first(fn (StudentSemester $row): bool => (int) $row->semester_id === (int) $globalSemester->id);

                if (! $studentSemester instanceof StudentSemester) {
                    $studentSemester = StudentSemester::query()->create([
                        'student_enrolment_id' => $enrolment->id,
                        'semester_id' => $globalSemester->id,
                        'programme_semester_id' => $programmeSemester->id,
                        'student_enrolment_status_id' => $activeStatusId,
                        'course_syllabus_ids' => $enrolment->course_syllabus_ids ?? [],
                    ]);
                } else {
                    $studentSemester->update([
                        'programme_semester_id' => $programmeSemester->id,
                        'student_enrolment_status_id' => $activeStatusId ?? $studentSemester->student_enrolment_status_id,
                    ]);
                }

                // Without this, StudentEnrolmentObserver::updated re-runs the phase sync, which
                // recreates every phase up to the current calendar period — immediately undoing a
                // backward correction. Matches SyncStudentSemestersForEnrolmentService.
                StudentEnrolment::withoutEvents(function () use ($enrolment, $globalSemester, $activeStatusId): void {
                    $enrolment->update([
                        'semester_id' => $globalSemester->id,
                        'student_enrolment_status_id' => $activeStatusId ?? $enrolment->student_enrolment_status_id,
                    ]);
                });
            } else {
                // Attachment phases have no global semester; pin by programme_semester_id only.
                $studentSemester = $enrolment->studentSemesters
                    ->first(fn (StudentSemester $row): bool => (int) $row->programme_semester_id === (int) $programmeSemester->id);

                if (! $studentSemester instanceof StudentSemester) {
                    $anchorSemesterId = $enrolment->semester_id
                        ?? $enrolment->studentSemesters->sortByDesc('id')->first()?->semester_id;

                    if ($anchorSemesterId === null) {
                        throw new InvalidArgumentException(__('trans.department_semester_reconciliation_phase_unmapped'));
                    }

                    // An attachment phase has no global semester of its own, so it has to borrow a
                    // calendar slot. A row for that slot almost always exists already (semester_id
                    // is NOT NULL and the sync observer backfills it), and creating a second one
                    // violates stu_sem_enrolment_semester_unq — the crash that made attachment
                    // phases impossible to assign. Reuse the row instead, and refuse rather than
                    // silently repurpose a slot that is pinned to a different phase.
                    $anchorRow = $enrolment->studentSemesters
                        ->first(fn (StudentSemester $row): bool => (int) $row->semester_id === (int) $anchorSemesterId);

                    if ($anchorRow instanceof StudentSemester) {
                        $anchorPin = $anchorRow->programme_semester_id;

                        if ($anchorPin !== null && (int) $anchorPin !== (int) $programmeSemester->id) {
                            throw new InvalidArgumentException(
                                __('trans.department_semester_reconciliation_phase_slot_conflict'),
                            );
                        }

                        $anchorRow->update([
                            'programme_semester_id' => $programmeSemester->id,
                            'student_enrolment_status_id' => $activeStatusId ?? $anchorRow->student_enrolment_status_id,
                        ]);

                        $studentSemester = $anchorRow;
                    } else {
                        $studentSemester = StudentSemester::query()->create([
                            'student_enrolment_id' => $enrolment->id,
                            'semester_id' => $anchorSemesterId,
                            'programme_semester_id' => $programmeSemester->id,
                            'student_enrolment_status_id' => $activeStatusId,
                            'course_syllabus_ids' => $enrolment->course_syllabus_ids ?? [],
                        ]);
                    }
                } else {
                    $studentSemester->update([
                        'programme_semester_id' => $programmeSemester->id,
                        'student_enrolment_status_id' => $activeStatusId ?? $studentSemester->student_enrolment_status_id,
                    ]);
                }
            }

            return $enrolment->fresh([
                'studentSemesters.semester',
                'studentSemesters.programmeSemester',
                'semester',
            ]) ?? $enrolment;
        });
    }

    /**
     * Calendar slugs wrap within the year, so several programme phases share one semester slot.
     * Re-pinning a slot is legitimate (it is how an advanced-standing student gets corrected), but
     * two rows pinned to the same programme phase is never valid — refuse rather than create it.
     */
    private function guardAgainstDuplicatePhasePin(
        StudentEnrolment $enrolment,
        ProgrammeSemester $programmeSemester,
        ?Semester $globalSemester,
    ): void {
        $targetSemesterId = $globalSemester?->id;

        $duplicate = $enrolment->studentSemesters
            ->first(function (StudentSemester $row) use ($programmeSemester, $targetSemesterId): bool {
                if ((int) $row->programme_semester_id !== (int) $programmeSemester->id) {
                    return false;
                }

                // The row we are about to write to is not a duplicate of itself.
                return $targetSemesterId === null || (int) $row->semester_id !== (int) $targetSemesterId;
            });

        if ($duplicate instanceof StudentSemester) {
            throw new InvalidArgumentException(
                __('trans.department_semester_reconciliation_phase_slot_conflict'),
            );
        }

        // A student's current phase is the highest-positioned record they hold. If a later phase
        // record already exists, writing this one cannot make it current, so the move would appear
        // to succeed while changing nothing. Refuse instead of reporting a false "moved" — clearing
        // the later records is a destructive call that belongs with the operator, not this action.
        $targetPosition = (int) $programmeSemester->position;

        $later = $enrolment->studentSemesters
            ->first(function (StudentSemester $row) use ($programmeSemester, $targetPosition): bool {
                if ((int) $row->programme_semester_id === (int) $programmeSemester->id) {
                    return false;
                }

                $position = $row->programmeSemester?->position;

                return $position !== null && (int) $position > $targetPosition;
            });

        if ($later instanceof StudentSemester) {
            throw new InvalidArgumentException(__('trans.department_semester_reconciliation_phase_later_records', [
                'phase' => (string) ($later->programmeSemester?->name ?? ''),
            ]));
        }
    }

    /**
     * Reconciliation corrects which phase a student sits in; it must not quietly readmit someone
     * who is deferred, absent, disqualified or referred. Those statuses are preserved and only a
     * non-blocking status is promoted to active.
     */
    private function resolveTargetStatusId(StudentEnrolment $enrolment): ?int
    {
        $currentStatusId = $enrolment->student_enrolment_status_id;

        if ($currentStatusId !== null) {
            foreach (StudentEnrolmentProgressionService::BLOCKING_STATUSES as $blockingSlug) {
                if ($this->progression->statusIdBySlug($blockingSlug) === (int) $currentStatusId) {
                    return (int) $currentStatusId;
                }
            }
        }

        return $this->progression->statusIdBySlug(StudentEnrolmentProgressionService::STATUS_ACTIVE)
            ?? $currentStatusId;
    }
}
