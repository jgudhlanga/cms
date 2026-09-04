<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Students;

use App\Actions\Students\StartNextLevelFromHexcoAwardAction;
use App\Enums\Institution\LevelEnum;
use App\Enums\Students\StudentExamResultComment;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\Examinations\ExaminationResult;
use App\Models\Institution\DepartmentLevel;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentExamResult;
use App\Models\Students\StudentSemester;
use App\Services\Institution\ProgrammeSemesterResolver;
use App\Services\Students\StudentEnrolmentProgressionService;
use App\Services\Students\StudentSemesterPhaseResolver;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use App\Support\Examinations\HexcoCourseLevelMatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repairs HEXCO awards that were filed against the wrong level.
 *
 * A statement's AWARD closes the level that statement covers. Until the statement's COURSE LEVEL
 * was carried through the import, the summary row was stamped with the student's *current*
 * enrolment instead, so an NC award landed on the first phase of the ND the student had just
 * started — ticking off a semester they had not sat.
 *
 * Selection is driven purely by exam evidence: a student with no student_exam_results row is
 * never inspected and never written to. After attribution is correct, a claimed AWARD also
 * starts the next level when a finalised next-level application exists.
 */
class ExamAwardPhaseRepairService
{
    public function __construct(
        protected ProgrammeSemesterResolver $programmeSemesterResolver,
        protected StudentEnrolmentProgressionService $progression,
        protected StudentSemesterPhaseResolver $phaseResolver,
        protected StartNextLevelFromHexcoAwardAction $startNextLevelFromHexcoAward,
    ) {}

    /**
     * @return array{
     *     run_id: int|null,
     *     repaired: list<array<string, mixed>>,
     *     skipped: list<array<string, mixed>>,
     *     next_level: list<array<string, mixed>>
     * }
     */
    public function run(bool $dryRun = true): array
    {
        $repaired = [];
        $skipped = [];
        $nextLevel = [];
        $runId = null;

        foreach ($this->studentsWithExamResults() as $student) {
            $finding = $this->inspect($student);

            if ($finding === null) {
                continue;
            }

            $latest = StudentExamResult::query()->find($finding['exam_result_id']);
            $attributionOk = $finding['skip_reason'] === 'nothing to repair';

            if ($finding['skip_reason'] !== null && ! $attributionOk) {
                $skipped[] = $finding;

                continue;
            }

            if ($attributionOk) {
                $skipped[] = $finding;
            } elseif (! $dryRun) {
                $runId ??= $this->startRun();
                $this->apply($finding, $runId);
                $repaired[] = $finding;
            } else {
                $repaired[] = $finding;
            }

            if ($dryRun || ! $latest instanceof StudentExamResult) {
                continue;
            }

            $outcome = $this->startNextLevelFromHexcoAward->execute($student, $latest->fresh() ?? $latest);
            $nextLevel[] = $outcome;
        }

        if ($runId !== null) {
            DB::table('programme_semester_progression_runs')
                ->where('id', $runId)
                ->update(['affected_count' => count($repaired), 'updated_at' => now()]);
        }

        return [
            'run_id' => $runId,
            'repaired' => $repaired,
            'skipped' => $skipped,
            'next_level' => $nextLevel,
        ];
    }

    /**
     * @return iterable<Student>
     */
    private function studentsWithExamResults(): iterable
    {
        return Student::query()
            ->whereIn('id', StudentExamResult::query()->select('student_id')->distinct())
            ->with('user')
            ->cursor();
    }

    /**
     * Decide whether this student's latest sitting is an award for a level below the one they
     * are now on — the signature of a mis-filed award.
     *
     * @return array<string, mixed>|null
     */
    private function inspect(Student $student): ?array
    {
        $latest = StudentExamResult::query()
            ->where('student_id', $student->id)
            ->orderByDesc('calendar_year')
            ->orderByDesc('session')
            ->first();

        if (! $latest instanceof StudentExamResult || $latest->comment !== StudentExamResultComment::Award) {
            return null;
        }

        $enrolment = $this->currentEnrolment($student);

        if (! $enrolment instanceof StudentEnrolment) {
            return null;
        }

        $currentLevel = $enrolment->studentApplication?->departmentLevel ?? $enrolment->departmentLevel;
        $currentPosition = (int) ($currentLevel?->level?->position ?? 0);

        if ($currentPosition === 0) {
            return null;
        }

        $awardPhases = $this->misplacedAwardPhases($enrolment);

        $awardLevel = $this->awardLevel(
            $student,
            $latest,
            $enrolment,
            $currentPosition,
            hasMisplacedAwardPhase: $awardPhases->isNotEmpty(),
        );

        if (! $awardLevel instanceof DepartmentLevel) {
            return null;
        }

        if ((int) ($awardLevel->level?->position ?? 0) >= $currentPosition) {
            return null;
        }

        $phantomPhase = $this->phantomLeadingPhase($enrolment);

        return [
            'student_id' => (int) $student->id,
            'student_number' => (string) $student->student_number,
            'student_enrolment_id' => (int) $enrolment->id,
            'exam_result_id' => (int) $latest->id,
            'session' => (string) $latest->session,
            'from_department_level_id' => $latest->department_level_id !== null ? (int) $latest->department_level_id : null,
            'to_department_level_id' => (int) $awardLevel->id,
            'to_level_name' => (string) ($awardLevel->level?->name ?? ''),
            'current_level_name' => (string) ($currentLevel?->level?->name ?? ''),
            'award_phase_ids' => $awardPhases->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            'phantom_phase_id' => $phantomPhase instanceof StudentSemester ? (int) $phantomPhase->id : null,
            'skip_reason' => $this->skipReason($latest, $awardPhases, $phantomPhase),
        ];
    }

    /**
     * The level the sitting belongs to.
     *
     * The statement's own COURSE LEVEL settles it outright. Legacy rows carry none, so we read the
     * level already on the row: filed below the student's current level it is already right, and
     * filed at their current level it can only be genuine if the student has actually reached the
     * phase that completes that level. Anything else is the mis-file, and belongs to the level
     * immediately below.
     */
    private function awardLevel(
        Student $student,
        StudentExamResult $result,
        StudentEnrolment $enrolment,
        int $currentPosition,
        bool $hasMisplacedAwardPhase = false,
    ): ?DepartmentLevel {
        $courseLevel = ExaminationResult::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $student->tenant_id)
            ->where('candidate_number', $result->candidate_number)
            ->where('session', $result->session)
            ->whereNotNull('course_level')
            ->value('course_level');

        $levelEnum = HexcoCourseLevelMatcher::match(is_string($courseLevel) ? $courseLevel : null);

        if ($levelEnum instanceof LevelEnum) {
            $matched = DepartmentLevel::query()
                ->where('institution_department_id', $enrolment->institution_department_id)
                ->whereHas('level', fn ($query) => $query->where('name', $levelEnum->name()))
                ->with('level')
                ->first();

            if ($matched instanceof DepartmentLevel) {
                return $matched;
            }
        }

        $result->loadMissing('departmentLevel.level');
        $filedPosition = (int) ($result->departmentLevel?->level?->position ?? 0);

        if ($filedPosition > 0 && $filedPosition < $currentPosition) {
            return $result->departmentLevel;
        }

        if (! $hasMisplacedAwardPhase && $this->reachedCompletionPhase($enrolment)) {
            return null;
        }

        return DepartmentLevel::query()
            ->where('institution_department_id', $enrolment->institution_department_id)
            ->whereHas('level', fn ($query) => $query->where('position', '<', $currentPosition))
            ->with('level')
            ->get()
            ->sortByDesc(fn (DepartmentLevel $departmentLevel): int => (int) ($departmentLevel->level?->position ?? 0))
            ->first();
    }

    /**
     * Has the student got as far as the phase that completes their current level? If so an award
     * filed against that level is plausible and must be left alone.
     */
    private function reachedCompletionPhase(StudentEnrolment $enrolment): bool
    {
        return $enrolment->studentSemesters->contains(
            fn (StudentSemester $phase): bool => $this->programmeSemesterResolver->isCompletionProgrammeSemester($phase),
        );
    }

    /**
     * Award-status phases on this enrolment that are not the level's completion phase.
     *
     * @return Collection<int, StudentSemester>
     */
    private function misplacedAwardPhases(StudentEnrolment $enrolment)
    {
        return $enrolment->studentSemesters
            ->filter(fn (StudentSemester $phase): bool => $this->progression->statusSlugForSemester($phase)
                === StudentEnrolmentProgressionService::STATUS_AWARD)
            ->reject(fn (StudentSemester $phase): bool => $this->programmeSemesterResolver
                ->isCompletionProgrammeSemester($phase))
            ->values();
    }

    /**
     * A phase earlier than the calendar period the enrolment was created in — produced by the old
     * "backfill every phase of the year" behaviour, never sat.
     *
     * Creation date, not the enrolment's current academic_calendar_id: that pointer moves forward
     * as a student advances, so a February intake now sitting on the year's second calendar would
     * otherwise look as though its genuine first phase were phantom. When no period contains the
     * creation date (registered between semesters) nothing is flagged.
     */
    private function phantomLeadingPhase(StudentEnrolment $enrolment): ?StudentSemester
    {
        $calendar = $enrolment->academicCalendar;

        if ($calendar === null || $enrolment->studentSemesters->count() < 2 || $enrolment->created_at === null) {
            return null;
        }

        $createdIn = AcademicCalendar::query()
            ->where('type', $calendar->type)
            ->whereDate('opening_date', '<=', $enrolment->created_at->toDateString())
            ->whereDate('closing_date', '>=', $enrolment->created_at->toDateString())
            ->orderBy('opening_date')
            ->first();

        if ($createdIn === null) {
            return null;
        }

        $startOrdinal = $this->phaseResolver->phaseOrdinal(
            AcademicCalendarPeriodResolver::semesterSlugForCalendar($createdIn),
        );

        return $enrolment->studentSemesters
            ->filter(fn (StudentSemester $phase): bool => $this->phaseResolver
                ->phaseOrdinal((string) ($phase->semester?->slug ?? '')) < $startOrdinal)
            ->sortBy('id')
            ->first();
    }

    /**
     * @param  Collection<int, StudentSemester>  $awardPhases
     */
    private function skipReason(
        StudentExamResult $result,
        $awardPhases,
        ?StudentSemester $phantomPhase,
    ): ?string {
        $needsLevelChange = (int) $result->department_level_id !== 0;

        if ($awardPhases->isEmpty() && $phantomPhase === null && ! $needsLevelChange) {
            return 'nothing to repair';
        }

        if ($phantomPhase instanceof StudentSemester && $this->phaseIsInUse($phantomPhase)) {
            return 'phantom phase is seated in a class or has pinned syllabi';
        }

        return null;
    }

    private function phaseIsInUse(StudentSemester $phase): bool
    {
        if (($phase->course_syllabus_ids ?? []) !== []) {
            return true;
        }

        if (StudentEnrolmentProgressionService::isBlockingStatus($this->progression->statusSlugForSemester($phase))) {
            return true;
        }

        return AcademicCalendarStudentEnrolment::query()
            ->where('student_semesters_id', $phase->id)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $finding
     */
    private function apply(array $finding, int $runId): void
    {
        DB::transaction(function () use ($finding, $runId): void {
            StudentExamResult::query()
                ->whereKey($finding['exam_result_id'])
                ->update(['department_level_id' => $finding['to_department_level_id']]);

            $enrolment = StudentEnrolment::query()
                ->with(['studentSemesters.semester', 'studentSemesters.studentEnrolmentStatus'])
                ->find($finding['student_enrolment_id']);

            if (! $enrolment instanceof StudentEnrolment) {
                return;
            }

            foreach ($finding['award_phase_ids'] as $phaseId) {
                if ($phaseId === $finding['phantom_phase_id']) {
                    // About to be removed entirely; no point restating its status first.
                    continue;
                }

                $phase = $enrolment->studentSemesters->firstWhere('id', $phaseId);

                if (! $phase instanceof StudentSemester) {
                    continue;
                }

                $this->clearMisplacedAward($enrolment, $phase, $runId);
            }

            if ($finding['phantom_phase_id'] !== null) {
                $this->collapsePhantomPhase($enrolment, (int) $finding['phantom_phase_id'], $runId);
            }
        });
    }

    /**
     * The student did sit this phase when a later one follows it, so it becomes Proceed; a phase
     * with nothing after it has no verified outcome and falls back to Unknown. Either way the
     * next profile render re-derives it from the real exam results.
     */
    private function clearMisplacedAward(StudentEnrolment $enrolment, StudentSemester $phase, int $runId): void
    {
        $ordinal = $this->phaseResolver->phaseOrdinal((string) ($phase->semester?->slug ?? ''));

        $hasLaterPhase = $enrolment->studentSemesters->contains(
            fn (StudentSemester $other): bool => $this->phaseResolver
                ->phaseOrdinal((string) ($other->semester?->slug ?? '')) > $ordinal,
        );

        $slug = $hasLaterPhase
            ? StudentEnrolmentProgressionService::STATUS_PROCEED
            : StudentEnrolmentProgressionService::STATUS_UNKNOWN;

        $statusId = $this->progression->statusIdBySlug($slug);

        if ($statusId === null) {
            return;
        }

        $this->recordRunItem($runId, $enrolment, $phase, $phase);
        $phase->update(['student_enrolment_status_id' => $statusId]);
    }

    private function collapsePhantomPhase(StudentEnrolment $enrolment, int $phaseId, int $runId): void
    {
        $phase = $enrolment->studentSemesters->firstWhere('id', $phaseId);

        if (! $phase instanceof StudentSemester) {
            return;
        }

        $this->recordRunItem($runId, $enrolment, $phase, null);
        $phase->delete();

        $survivors = $enrolment->studentSemesters
            ->reject(fn (StudentSemester $row): bool => (int) $row->id === $phaseId)
            ->sortBy(fn (StudentSemester $row): int => $this->phaseResolver
                ->phaseOrdinal((string) ($row->semester?->slug ?? '')))
            ->values();

        $dlc = $this->programmeSemesterResolver->resolveDepartmentLevelCourse($enrolment);

        if ($dlc === null) {
            return;
        }

        $taught = $this->programmeSemesterResolver->taughtProgrammeSemesters($dlc);

        foreach ($survivors as $index => $survivor) {
            if (! isset($taught[$index])) {
                continue;
            }

            $survivor->update(['programme_semester_id' => $taught[$index]->id]);
        }
    }

    private function recordRunItem(
        int $runId,
        StudentEnrolment $enrolment,
        StudentSemester $previous,
        ?StudentSemester $new,
    ): void {
        DB::table('programme_semester_progression_run_items')->insert([
            'programme_semester_progression_run_id' => $runId,
            'student_enrolment_id' => $enrolment->id,
            'previous_student_semester_id' => $previous->id,
            'new_student_semester_id' => $new?->id,
            'previous_pivot_id' => null,
            'new_pivot_id' => null,
            'previous_programme_semester_id' => $previous->programme_semester_id,
            'new_programme_semester_id' => $new?->programme_semester_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function startRun(): int
    {
        return (int) DB::table('programme_semester_progression_runs')->insertGetId([
            'tenant_id' => null,
            'academic_calendar_class_id' => null,
            'triggered_by' => null,
            'action' => 'repair_award_phase',
            'affected_count' => 0,
            'dry_run' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function currentEnrolment(Student $student): ?StudentEnrolment
    {
        return StudentEnrolment::query()
            ->where('student_id', $student->id)
            ->with([
                'academicCalendar',
                'departmentLevel.level',
                'studentApplication.departmentLevel.level',
                'studentSemesters.semester',
                'studentSemesters.studentEnrolmentStatus',
            ])
            ->get()
            ->sortByDesc(fn (StudentEnrolment $enrolment): string => (string) ($enrolment->academicCalendar?->opening_date ?? ''))
            ->first();
    }
}
