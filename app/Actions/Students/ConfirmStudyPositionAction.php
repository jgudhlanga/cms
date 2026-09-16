<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Enums\Students\StudyPositionAnswerEnum;
use App\Enums\Students\StudyPositionSourceEnum;
use App\Enums\Students\StudyPositionSyncStatusEnum;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
use App\Models\Students\StudentStudyPositionConfirmation;
use App\Models\Users\User;
use App\Services\Institution\ProgrammeSemesterResolver;
use App\Services\Students\StudyPosition\CurrentStudyPeriod;
use App\Services\Students\StudyPosition\CurrentStudyPeriodResolver;
use App\Services\Students\StudyPosition\StudyPositionScope;
use App\Services\Students\StudyPosition\StudyPositionService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Records which programme phase an enrolment is studying in the current period and, when that
 * differs from the records, moves the records through the same guarded write the department
 * semester reconciliation uses. A write the guards refuse never rewrites history: a student's
 * answer is kept and flagged for registry review, an admin is told why, and an automatic rule
 * simply stands down so the student is asked instead.
 *
 * Returns null when an automatic or department source had nothing it may record.
 */
class ConfirmStudyPositionAction
{
    public function __construct(
        protected SetStudentEnrolmentCurrentPhaseAction $setCurrentPhase,
        protected ProgrammeSemesterResolver $programmeSemesterResolver,
        protected CurrentStudyPeriodResolver $periods,
        protected StudyPositionScope $scope,
    ) {}

    /**
     * @param  array<string, mixed>  $evidence
     */
    public function execute(
        StudentEnrolment $enrolment,
        StudyPositionAnswerEnum $answer,
        ?ProgrammeSemester $phase,
        StudyPositionSourceEnum $source,
        ?User $actor = null,
        ?string $reason = null,
        array $evidence = [],
    ): ?StudentStudyPositionConfirmation {
        $period = $this->periods->forEnrolment($enrolment);

        if (! $period instanceof CurrentStudyPeriod || ! $this->isInScope($enrolment)) {
            return $this->refuse($source, __('students.study_position_invalid_programme'));
        }

        if ($answer === StudyPositionAnswerEnum::PHASE && ! $phase instanceof ProgrammeSemester) {
            return $this->refuse($source, __('students.study_position_answer_required'));
        }

        if ($answer->isFollowUp()) {
            $phase = null;
        }

        $attempt = fn (): ?StudentStudyPositionConfirmation => DB::transaction(
            fn (): ?StudentStudyPositionConfirmation => $this->record(
                $enrolment, $period, $answer, $phase, $source, $actor, $reason, $evidence,
            ),
        );

        try {
            $confirmation = $attempt();
        } catch (UniqueConstraintViolationException) {
            // A concurrent request created the row first; the retry sees it and applies precedence.
            $confirmation = $attempt();
        }

        StudyPositionService::forget((int) $enrolment->student_id);

        return $confirmation;
    }

    /**
     * @param  array<string, mixed>  $evidence
     */
    private function record(
        StudentEnrolment $enrolment,
        CurrentStudyPeriod $period,
        StudyPositionAnswerEnum $answer,
        ?ProgrammeSemester $phase,
        StudyPositionSourceEnum $source,
        ?User $actor,
        ?string $reason,
        array $evidence,
    ): ?StudentStudyPositionConfirmation {
        $existing = StudentStudyPositionConfirmation::query()
            ->withoutGlobalScopes()
            ->where('student_enrolment_id', $enrolment->id)
            ->where('academic_calendar_id', $period->periodId())
            ->lockForUpdate()
            ->first();

        $enrolment->unsetRelation('studentSemesters');
        $systemRow = $enrolment->currentStudentSemester();
        $systemPhase = $this->phaseForRow($systemRow);

        if ($existing instanceof StudentStudyPositionConfirmation) {
            $blocked = $this->applyPrecedence($existing, $answer, $phase, $source, $systemPhase);

            if ($blocked !== false) {
                return $blocked;
            }
        }

        $confirmation = $existing ?? new StudentStudyPositionConfirmation([
            'student_enrolment_id' => $enrolment->id,
            'academic_calendar_id' => $period->periodId(),
        ]);

        $base = [
            'tenant_id' => $this->tenantIdFor($enrolment),
            'student_id' => $enrolment->student_id,
            'semester_id' => $period->slot->id,
            'answer' => $answer,
            'source' => $source,
            'reason' => $reason !== null && trim($reason) !== '' ? trim($reason) : null,
            'evidence' => $evidence === [] ? null : $evidence,
            'confirmed_by' => $actor?->id,
            'confirmed_at' => now(),
            'previous_programme_semester_id' => $systemPhase?->id,
        ];

        if ($answer->isFollowUp()) {
            return $this->save($confirmation, $enrolment, $period, $actor, $base + [
                'programme_semester_id' => null,
                'student_semester_id' => $systemRow?->id,
                'sync_status' => StudyPositionSyncStatusEnum::NOT_APPLICABLE,
                'sync_note' => null,
            ], $systemPhase, null);
        }

        /** @var ProgrammeSemester $phase */
        $invalid = $this->invalidPhaseReason($enrolment, $phase, $source);

        if ($invalid !== null) {
            return $this->refuse($source, $invalid);
        }

        $base['programme_semester_id'] = $phase->id;

        if ($this->recordsAlreadyMatch($systemRow, $systemPhase, $phase, $period)) {
            return $this->save($confirmation, $enrolment, $period, $actor, $base + [
                'student_semester_id' => $systemRow?->id,
                'sync_status' => StudyPositionSyncStatusEnum::UNCHANGED,
                'sync_note' => null,
            ], $systemPhase, $phase);
        }

        if ($source !== StudyPositionSourceEnum::ADMIN && ! $source->isDepartment()) {
            $conflict = $this->earlierEnrolmentConflict($enrolment, $phase, $period);

            if ($conflict !== null) {
                if ($source !== StudyPositionSourceEnum::STUDENT) {
                    return null;
                }

                return $this->save($confirmation, $enrolment, $period, $actor, $base + [
                    'student_semester_id' => $systemRow?->id,
                    'sync_status' => StudyPositionSyncStatusEnum::NEEDS_REVIEW,
                    'sync_note' => $conflict,
                ], $systemPhase, $phase);
            }
        }

        try {
            $fresh = $this->setCurrentPhase->execute($enrolment, $phase, $period->slot);
        } catch (InvalidArgumentException $exception) {
            if ($source === StudyPositionSourceEnum::ADMIN) {
                throw ValidationException::withMessages(['programme_semester_id' => $exception->getMessage()]);
            }

            if ($source !== StudyPositionSourceEnum::STUDENT) {
                return null;
            }

            return $this->save($confirmation, $enrolment, $period, $actor, $base + [
                'student_semester_id' => $systemRow?->id,
                'sync_status' => StudyPositionSyncStatusEnum::NEEDS_REVIEW,
                'sync_note' => $exception->getMessage(),
            ], $systemPhase, $phase);
        }

        $writtenRow = $fresh->studentSemesters
            ->first(fn (StudentSemester $row): bool => (int) $row->semester_id === (int) $period->slot->id);

        return $this->save($confirmation, $enrolment, $period, $actor, $base + [
            'student_semester_id' => $writtenRow?->id,
            'sync_status' => StudyPositionSyncStatusEnum::APPLIED,
            'sync_note' => null,
        ], $systemPhase, $phase);
    }

    /**
     * Returns false when the new answer may replace the existing one; otherwise what to return.
     */
    private function applyPrecedence(
        StudentStudyPositionConfirmation $existing,
        StudyPositionAnswerEnum $answer,
        ?ProgrammeSemester $phase,
        StudyPositionSourceEnum $source,
        ?ProgrammeSemester $systemPhase,
    ): StudentStudyPositionConfirmation|false|null {
        if ($source === StudyPositionSourceEnum::ADMIN) {
            return false;
        }

        if ($source === StudyPositionSourceEnum::STUDENT) {
            $mayReplace = $existing->source === StudyPositionSourceEnum::STUDENT
                && $existing->answer->isFollowUp();

            if (! $mayReplace) {
                throw ValidationException::withMessages([
                    'answers' => __('students.study_position_already_confirmed'),
                ]);
            }

            return false;
        }

        if ($source->isDepartment()) {
            if (! $existing->source->isHuman()) {
                return false;
            }

            // Staff-verified data settles a student who could not say where they are.
            if ($existing->source === StudyPositionSourceEnum::STUDENT && $existing->answer->isFollowUp()) {
                return false;
            }

            $agrees = $existing->answer === StudyPositionAnswerEnum::PHASE
                && $phase instanceof ProgrammeSemester
                && (int) $existing->programme_semester_id === (int) $phase->id;

            if (! $agrees && $existing->answer === StudyPositionAnswerEnum::PHASE) {
                $existing->update([
                    'sync_status' => StudyPositionSyncStatusEnum::NEEDS_REVIEW,
                    'sync_note' => __('students.study_position_department_disagrees', [
                        'phase' => (string) ($phase?->name ?? $systemPhase?->name ?? ''),
                    ]),
                ]);
            }

            return $existing;
        }

        // Automatic rules never overwrite anything already recorded.
        return $existing;
    }

    private function invalidPhaseReason(
        StudentEnrolment $enrolment,
        ProgrammeSemester $phase,
        StudyPositionSourceEnum $source,
    ): ?string {
        $dlc = $this->programmeSemesterResolver->resolveDepartmentLevelCourse($enrolment);

        if ($dlc === null || (int) $phase->department_level_course_id !== (int) $dlc->id) {
            return __('trans.department_semester_reconciliation_phase_wrong_offering');
        }

        if ($source === StudyPositionSourceEnum::STUDENT) {
            $allowed = $this->programmeSemesterResolver->phaseOptionsForEnrolment($enrolment)
                ->contains(fn (ProgrammeSemester $option): bool => (int) $option->id === (int) $phase->id);

            if (! $allowed) {
                return __('students.study_position_invalid_phase');
            }
        }

        return null;
    }

    /**
     * The records already say this: the current phase is the confirmed one and it sits in this
     * period's slot (attachment phases borrow a slot, so any slot will do for them).
     */
    private function recordsAlreadyMatch(
        ?StudentSemester $systemRow,
        ?ProgrammeSemester $systemPhase,
        ProgrammeSemester $phase,
        CurrentStudyPeriod $period,
    ): bool {
        if (! $systemRow instanceof StudentSemester || ! $systemPhase instanceof ProgrammeSemester) {
            return false;
        }

        if ((int) $systemPhase->id !== (int) $phase->id || $systemRow->programme_semester_id === null) {
            return false;
        }

        return ! $phase->isTaught() || (int) $systemRow->semester_id === (int) $period->slot->id;
    }

    /**
     * An earlier year of the same level and course already holds this phase (a repeat) or a later
     * one. Recording it here would contradict that history, so a person has to look first.
     */
    private function earlierEnrolmentConflict(
        StudentEnrolment $enrolment,
        ProgrammeSemester $phase,
        CurrentStudyPeriod $period,
    ): ?string {
        $conflict = StudentSemester::query()
            ->join('student_enrolments as sp_prior', 'sp_prior.id', '=', 'student_semesters.student_enrolment_id')
            ->join('academic_calendars as sp_prior_cal', 'sp_prior_cal.id', '=', 'sp_prior.academic_calendar_id')
            ->join('programme_semesters as sp_prior_phase', 'sp_prior_phase.id', '=', 'student_semesters.programme_semester_id')
            ->where('sp_prior.student_id', $enrolment->student_id)
            ->where('sp_prior.department_level_id', $enrolment->department_level_id)
            ->where('sp_prior.department_course_id', $enrolment->department_course_id)
            ->where('sp_prior.id', '!=', $enrolment->id)
            ->whereNull('sp_prior.deleted_at')
            ->whereNull('sp_prior_phase.deleted_at')
            ->where('sp_prior_cal.calendar_year', '<', $period->calendarYear)
            ->where('sp_prior_phase.position', '>=', $phase->position)
            ->orderByDesc('sp_prior_phase.position')
            ->first([
                'sp_prior_phase.name as conflict_phase',
                'sp_prior_phase.position as conflict_position',
                'sp_prior_cal.calendar_year as conflict_year',
            ]);

        if ($conflict === null) {
            return null;
        }

        $key = (int) $conflict->getAttribute('conflict_position') === (int) $phase->position
            ? 'students.study_position_conflict_repeat'
            : 'students.study_position_conflict_later_year';

        return __($key, [
            'phase' => (string) $conflict->getAttribute('conflict_phase'),
            'year' => (string) $conflict->getAttribute('conflict_year'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function save(
        StudentStudyPositionConfirmation $confirmation,
        StudentEnrolment $enrolment,
        CurrentStudyPeriod $period,
        ?User $actor,
        array $attributes,
        ?ProgrammeSemester $previousPhase,
        ?ProgrammeSemester $newPhase,
    ): StudentStudyPositionConfirmation {
        $confirmation->fill($attributes)->save();

        $this->audit($confirmation, $enrolment, $period, $actor, $previousPhase, $newPhase);

        return $confirmation;
    }

    private function audit(
        StudentStudyPositionConfirmation $confirmation,
        StudentEnrolment $enrolment,
        CurrentStudyPeriod $period,
        ?User $actor,
        ?ProgrammeSemester $previousPhase,
        ?ProgrammeSemester $newPhase,
    ): void {
        $student = Student::query()->withoutGlobalScopes()->find($enrolment->student_id);

        if (! $student instanceof Student) {
            return;
        }

        $answerLabel = $newPhase?->name ?? $confirmation->answer->label();

        $logger = activity('Student')
            ->performedOn($student)
            ->event('study-position-confirmed')
            ->withProperties([
                'student_enrolment_id' => $enrolment->id,
                'academic_calendar_id' => $period->periodId(),
                'period' => $period->label,
                'old_programme_semester_id' => $previousPhase?->id,
                'old_programme_semester' => $previousPhase?->name,
                'new_programme_semester_id' => $newPhase?->id,
                'new_programme_semester' => $newPhase?->name,
                'answer' => $confirmation->answer->value,
                'source' => $confirmation->source->value,
                'sync_status' => $confirmation->sync_status->value,
                'sync_note' => $confirmation->sync_note,
                'reason' => $confirmation->reason,
            ]);

        if ($actor instanceof User) {
            $logger->causedBy($actor);
        }

        $logger->log(__('students.study_position_activity_description', [
            'period' => $period->label,
            'phase' => $answerLabel,
            'source' => $confirmation->source->label(),
        ]));
    }

    private function phaseForRow(?StudentSemester $row): ?ProgrammeSemester
    {
        if (! $row instanceof StudentSemester) {
            return null;
        }

        return $row->programmeSemester
            ?? $this->programmeSemesterResolver->programmeSemesterForStudentSemester($row);
    }

    private function isInScope(StudentEnrolment $enrolment): bool
    {
        return $this->scope
            ->constrain(StudentEnrolment::query()->whereKey($enrolment->id))
            ->exists();
    }

    private function tenantIdFor(StudentEnrolment $enrolment): ?int
    {
        $tenantId = Student::query()->withoutGlobalScopes()->whereKey($enrolment->student_id)->value('tenant_id');

        return $tenantId !== null ? (int) $tenantId : null;
    }

    /**
     * People are told why; automatic sources quietly record nothing.
     */
    private function refuse(StudyPositionSourceEnum $source, string $message): ?StudentStudyPositionConfirmation
    {
        if ($source->isHuman()) {
            $key = $source === StudyPositionSourceEnum::ADMIN ? 'programme_semester_id' : 'answers';

            throw ValidationException::withMessages([$key => $message]);
        }

        return null;
    }
}
