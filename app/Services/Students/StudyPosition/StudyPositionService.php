<?php

declare(strict_types=1);

namespace App\Services\Students\StudyPosition;

use App\Enums\Students\StudyPositionAnswerEnum;
use App\Enums\Students\StudyPositionSourceEnum;
use App\Enums\Students\StudyPositionStateEnum;
use App\Enums\Students\StudyPositionSyncStatusEnum;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentStudyPositionConfirmation;
use App\Models\Users\User;
use App\Services\Institution\ProgrammeSemesterResolver;
use App\Support\Institution\ProgrammeSemesterNameFormatter;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;

/**
 * Where a student stands on confirming their study position this period: what the prompt, the
 * banners and the admin modal render.
 */
class StudyPositionService
{
    private const PROMPT_CACHE_PREFIX = 'study-position:prompt:';

    private const PROMPT_CACHE_TTL_SECONDS = 600;

    private const AUTODETECT_GUARD_HOURS = 6;

    public function __construct(
        private readonly CurrentStudyPeriodResolver $periods,
        private readonly StudyPositionScope $scope,
        private readonly ProgrammeSemesterResolver $programmeSemesterResolver,
        private readonly StudyPositionAutoConfirmer $autoConfirmer,
    ) {}

    public static function forget(int $studentId): void
    {
        Cache::forget(self::PROMPT_CACHE_PREFIX.$studentId);
    }

    /**
     * @return EloquentCollection<int, StudentEnrolment>
     */
    public function inScopeEnrolments(Student $student): EloquentCollection
    {
        $periodIds = $this->scope->currentPeriodIds();

        return $this->scope
            ->constrain(StudentEnrolment::query()->where('student_enrolments.student_id', $student->id))
            ->with([
                'departmentLevel.level',
                'departmentCourse.course',
                'institutionDepartment.department',
                'modeOfStudy',
                'studentSemesters.semester',
                'studentSemesters.programmeSemester',
                'studyPositionConfirmations' => fn ($query) => $query
                    ->withoutGlobalScopes()
                    ->forPeriods($periodIds)
                    ->with(['programmeSemester', 'previousProgrammeSemester', 'confirmedByUser']),
            ])
            ->orderBy('student_enrolments.id')
            ->get();
    }

    /**
     * @return array{
     *     state: string|null,
     *     required: bool,
     *     periodLabel: string|null,
     *     items: list<array<string, mixed>>
     * }
     */
    public function statusFor(
        Student $student,
        ?User $viewer = null,
        bool $forAdmin = false,
        bool $autoDetect = true,
    ): array {
        $enrolments = $this->inScopeEnrolments($student);

        if ($autoDetect && $this->autoDetect($enrolments)) {
            $enrolments = $this->inScopeEnrolments($student);
        }

        $items = [];

        foreach ($enrolments as $enrolment) {
            $item = $this->itemPayload($student, $enrolment, $viewer, $forAdmin);

            if ($item !== null) {
                $items[] = $item;
            }
        }

        $worst = null;

        foreach ($items as $item) {
            $state = StudyPositionStateEnum::from($item['state']);

            if ($worst === null || $state->severity() > $worst->severity()) {
                $worst = $state;
            }
        }

        $periodLabels = array_values(array_unique(array_map(
            static fn (array $item): string => (string) $item['period']['label'],
            $items,
        )));

        return [
            'state' => $worst?->value,
            'required' => collect($items)->contains(
                fn (array $item): bool => $item['state'] === StudyPositionStateEnum::UNCONFIRMED->value,
            ),
            'periodLabel' => $periodLabels === [] ? null : implode(' / ', $periodLabels),
            'items' => $items,
        ];
    }

    /**
     * The small, cached slice shared with every portal page: enough to decide whether to force the
     * prompt and what the banner says. Keyed by the period fingerprint so a new period refreshes it.
     *
     * @return array{
     *     state: string|null,
     *     required: bool,
     *     periodLabel: string|null,
     *     items: list<array{enrolmentId: int, programme: string, state: string, answeredPhase: string|null, systemPhase: string|null}>
     * }|null
     */
    public function promptSummary(Student $student): ?array
    {
        $fingerprint = $this->scope->fingerprint();
        $key = self::PROMPT_CACHE_PREFIX.$student->id;
        $cached = Cache::get($key);

        if (is_array($cached) && ($cached['fingerprint'] ?? null) === $fingerprint) {
            return $cached['summary'];
        }

        $status = $this->statusFor($student);

        $summary = $status['items'] === [] ? null : [
            'state' => $status['state'],
            'required' => $status['required'],
            'periodLabel' => $status['periodLabel'],
            'items' => array_map(static fn (array $item): array => [
                'enrolmentId' => $item['enrolmentId'],
                'programme' => $item['programme']['label'],
                'state' => $item['state'],
                'answeredPhase' => $item['confirmation']['phase']['label'] ?? null,
                'systemPhase' => $item['systemPhase']['label'] ?? null,
            ], $status['items']),
        ];

        Cache::put($key, ['fingerprint' => $fingerprint, 'summary' => $summary], self::PROMPT_CACHE_TTL_SECONDS);

        return $summary;
    }

    /**
     * Run the automatic rules once per enrolment per period (guarded, so page loads stay cheap).
     *
     * @param  EloquentCollection<int, StudentEnrolment>  $enrolments
     */
    private function autoDetect(EloquentCollection $enrolments): bool
    {
        $ran = false;

        foreach ($enrolments as $enrolment) {
            if ($enrolment->studyPositionConfirmations->isNotEmpty()) {
                continue;
            }

            $period = $this->periods->forEnrolment($enrolment);

            if (! $period instanceof CurrentStudyPeriod) {
                continue;
            }

            $guard = "study-position:autodetect:{$enrolment->id}:{$period->periodId()}";

            if (! Cache::add($guard, true, now()->addHours(self::AUTODETECT_GUARD_HOURS))) {
                continue;
            }

            $outcome = $this->autoConfirmer->run($enrolment, $period);
            $ran = $ran || in_array($outcome, [StudyPositionAutoConfirmer::APPLIED, StudyPositionAutoConfirmer::KEPT], true);
        }

        return $ran;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function itemPayload(Student $student, StudentEnrolment $enrolment, ?User $viewer, bool $forAdmin): ?array
    {
        $period = $this->periods->forEnrolment($enrolment);

        if (! $period instanceof CurrentStudyPeriod) {
            return null;
        }

        /** @var StudentStudyPositionConfirmation|null $confirmation */
        $confirmation = $enrolment->studyPositionConfirmations
            ->first(fn (StudentStudyPositionConfirmation $row): bool => (int) $row->academic_calendar_id === $period->periodId());

        $levelName = $enrolment->departmentLevel?->level?->name;
        $courseName = $enrolment->departmentCourse?->course?->name;
        $systemRow = $enrolment->currentStudentSemester();
        $systemPhase = $systemRow?->programmeSemester
            ?? ($systemRow !== null ? $this->programmeSemesterResolver->programmeSemesterForStudentSemester($systemRow) : null);
        $state = StudyPositionStateEnum::fromConfirmation($confirmation);

        $options = $this->programmeSemesterResolver->phaseOptionsForEnrolment($enrolment)
            ->map(fn (ProgrammeSemester $phase): array => $this->phasePayload($phase, $levelName))
            ->values()
            ->all();

        $studentCanAnswer = $state === StudyPositionStateEnum::UNCONFIRMED
            || ($state === StudyPositionStateEnum::FOLLOW_UP
                && $confirmation?->source === StudyPositionSourceEnum::STUDENT);

        $drift = $confirmation instanceof StudentStudyPositionConfirmation
            && $confirmation->answer === StudyPositionAnswerEnum::PHASE
            && $confirmation->sync_status->isSettled()
            && $systemPhase instanceof ProgrammeSemester
            && (int) $confirmation->programme_semester_id !== (int) $systemPhase->id;

        return [
            'enrolmentId' => (int) $enrolment->id,
            'programme' => [
                'label' => trim(($courseName ?? '').($levelName !== null ? " ({$levelName})" : '')),
                'course' => $courseName,
                'level' => $levelName,
                'department' => $enrolment->institutionDepartment?->department?->name,
                'modeOfStudy' => $enrolment->modeOfStudy?->name,
            ],
            'period' => [
                'id' => $period->periodId(),
                'label' => $period->label,
                'slotName' => $period->slotName,
                'calendarYear' => $period->calendarYear,
            ],
            'systemPhase' => $systemPhase instanceof ProgrammeSemester ? $this->phasePayload($systemPhase, $levelName) : null,
            'options' => $options,
            'confirmation' => $confirmation instanceof StudentStudyPositionConfirmation
                ? $this->confirmationPayload($confirmation, $levelName, $forAdmin)
                : null,
            'state' => $state->value,
            'drift' => $drift,
            'studentCanAnswer' => $studentCanAnswer,
            'canConfirm' => $forAdmin
                && $viewer instanceof User
                && $viewer->can('confirmStudyPosition', [$student, $enrolment]),
        ];
    }

    /**
     * @return array{id: int, name: string, label: string, position: int, kind: string}
     */
    private function phasePayload(ProgrammeSemester $phase, ?string $levelName): array
    {
        $kind = $phase->kind;

        return [
            'id' => (int) $phase->id,
            'name' => (string) $phase->name,
            'label' => ProgrammeSemesterNameFormatter::qualifiedName($levelName, (string) $phase->name),
            'position' => (int) $phase->position,
            'kind' => is_string($kind) ? $kind : ($kind?->value ?? 'taught'),
        ];
    }

    /**
     * Guard messages and admin reasons stay on the admin side; students get their answer and status.
     *
     * @return array<string, mixed>
     */
    private function confirmationPayload(
        StudentStudyPositionConfirmation $confirmation,
        ?string $levelName,
        bool $forAdmin,
    ): array {
        $payload = [
            'answer' => $confirmation->answer->value,
            'answerLabel' => $confirmation->answer->label(),
            'phase' => $confirmation->programmeSemester instanceof ProgrammeSemester
                ? $this->phasePayload($confirmation->programmeSemester, $levelName)
                : null,
            'source' => $confirmation->source->value,
            'sourceLabel' => $confirmation->source->label(),
            'syncStatus' => $confirmation->sync_status->value,
            'confirmedAt' => $confirmation->confirmed_at?->toIso8601String(),
        ];

        if (! $forAdmin) {
            return $payload;
        }

        return $payload + [
            'previousPhase' => $confirmation->previousProgrammeSemester instanceof ProgrammeSemester
                ? $this->phasePayload($confirmation->previousProgrammeSemester, $levelName)
                : null,
            'syncNote' => $confirmation->sync_note,
            'reason' => $confirmation->reason,
            'confirmedBy' => $confirmation->confirmedByUser?->full_name,
            'needsReview' => $confirmation->sync_status === StudyPositionSyncStatusEnum::NEEDS_REVIEW,
        ];
    }
}
