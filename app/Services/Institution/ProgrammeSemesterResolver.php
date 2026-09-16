<?php

declare(strict_types=1);

namespace App\Services\Institution;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Institution\ProgrammeSemesterKindEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
use App\Services\Students\StudentSemesterPhaseResolver;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use Illuminate\Support\Collection;

class ProgrammeSemesterResolver
{
    /** @var array<string, DepartmentLevelCourse|null> */
    private array $departmentLevelCourseCache = [];

    public function __construct(
        protected StudentSemesterPhaseResolver $phaseResolver,
    ) {}

    public function resolveDepartmentLevelCourse(StudentEnrolment $enrolment): ?DepartmentLevelCourse
    {
        $enrolment->loadMissing(['departmentLevel', 'studentApplication.departmentLevel']);

        $departmentLevelId = $enrolment->department_level_id
            ?? $enrolment->studentApplication?->department_level_id;
        $departmentCourseId = $enrolment->department_course_id
            ?? $enrolment->studentApplication?->department_course_id;

        if ($departmentLevelId === null || $departmentCourseId === null) {
            return null;
        }

        $cacheKey = $departmentLevelId.':'.$departmentCourseId;

        if (array_key_exists($cacheKey, $this->departmentLevelCourseCache)) {
            return $this->departmentLevelCourseCache[$cacheKey];
        }

        $this->departmentLevelCourseCache[$cacheKey] = DepartmentLevelCourse::query()
            ->where('department_level_id', $departmentLevelId)
            ->where('department_course_id', $departmentCourseId)
            ->with(['programmeSemesters', 'departmentLevel.level'])
            ->first();

        return $this->departmentLevelCourseCache[$cacheKey];
    }

    public function resolveDepartmentLevelCourseForOffering(
        int $departmentCourseId,
        int $departmentLevelId,
    ): ?DepartmentLevelCourse {
        return DepartmentLevelCourse::query()
            ->where('department_course_id', $departmentCourseId)
            ->where('department_level_id', $departmentLevelId)
            ->with(['programmeSemesters', 'departmentLevel.level'])
            ->first();
    }

    /**
     * @return Collection<int, ProgrammeSemester>
     */
    public function programmeSemestersForEnrolment(StudentEnrolment $enrolment): Collection
    {
        $dlc = $this->resolveDepartmentLevelCourse($enrolment);

        if ($dlc === null) {
            return collect();
        }

        return $dlc->programmeSemesters ?? collect();
    }

    public function programmeSemesterForStudentSemester(StudentSemester $studentSemester): ?ProgrammeSemester
    {
        if ($studentSemester->programme_semester_id !== null) {
            return ProgrammeSemester::query()->find($studentSemester->programme_semester_id);
        }

        $studentSemester->loadMissing(['enrolment', 'semester']);

        $enrolment = $studentSemester->enrolment;

        if (! $enrolment instanceof StudentEnrolment || $studentSemester->semester_id === null) {
            return null;
        }

        $dlc = $this->resolveDepartmentLevelCourse($enrolment);

        if ($dlc === null) {
            return null;
        }

        return $this->mapGlobalSemesterToProgrammeSemester(
            $dlc,
            (int) $studentSemester->semester_id,
            $this->startOrdinalForEnrolment($enrolment),
        );
    }

    /**
     * The calendar phase this enrolment began in. A mid-year intake's first phase is the year's
     * second calendar semester, so its programme phases are offset by one.
     */
    private function startOrdinalForEnrolment(StudentEnrolment $enrolment): int
    {
        // Queried rather than eager-loaded so callers keep whatever relation state they had.
        $ordinals = StudentSemester::query()
            ->where('student_semesters.student_enrolment_id', $enrolment->id)
            ->join('semesters', 'semesters.id', '=', 'student_semesters.semester_id')
            ->pluck('semesters.slug')
            ->map(fn ($slug): int => $this->phaseResolver->phaseOrdinal((string) $slug))
            ->filter(fn (int $ordinal): bool => $ordinal > 0);

        return $ordinals->isEmpty() ? 1 : (int) $ordinals->min();
    }

    public function mapGlobalSemesterToProgrammeSemester(
        DepartmentLevelCourse $dlc,
        int $globalSemesterId,
        int $startOrdinal = 1,
    ): ?ProgrammeSemester {
        $dlc->loadMissing(['programmeSemesters', 'departmentLevel.level']);

        $programmeSemesters = $dlc->programmeSemesters;

        if ($programmeSemesters === null || $programmeSemesters->isEmpty()) {
            return null;
        }

        $calendarType = $dlc->departmentLevel?->level?->calendar_type;

        if (! $calendarType instanceof AcademicCalendarTypeEnum) {
            $calendarType = AcademicCalendarTypeEnum::tryFrom((string) $calendarType)
                ?? AcademicCalendarTypeEnum::SEMESTER;
        }

        $globalSemester = Semester::query()->find($globalSemesterId);

        if ($globalSemester === null) {
            return null;
        }

        $ordinal = $this->phaseResolver->phaseOrdinal((string) $globalSemester->slug);

        /** @var Collection<int, ProgrammeSemester> $taught */
        $taught = $programmeSemesters
            ->where('kind', ProgrammeSemesterKindEnum::TAUGHT)
            ->sortBy('position')
            ->values();

        // Offset by the period the student started in, wrapping within the year: an August intake
        // begins their Year 1 Sem 1 in the calendar's second semester, so the year's first
        // semester is where their Year 1 Sem 2 will fall.
        $periodsPerYear = max(1, $calendarType->maxAssessmentCalendarsPerYear());
        $index = (($ordinal - max(1, $startOrdinal)) % $periodsPerYear + $periodsPerYear) % $periodsPerYear;

        /** @var ProgrammeSemester|null $match */
        $match = $taught->get($index);

        return $match instanceof ProgrammeSemester ? $match : null;
    }

    public function globalSemesterForProgrammeSemester(
        DepartmentLevelCourse $dlc,
        ProgrammeSemester $programmeSemester,
    ): ?Semester {
        $dlc->loadMissing(['departmentLevel.level', 'programmeSemesters']);

        $calendarType = $dlc->departmentLevel?->level?->calendar_type;

        if (! $calendarType instanceof AcademicCalendarTypeEnum) {
            $calendarType = AcademicCalendarTypeEnum::tryFrom((string) $calendarType)
                ?? AcademicCalendarTypeEnum::SEMESTER;
        }

        if (! $programmeSemester->isTaught()) {
            return null;
        }

        $periodsPerYear = max(1, $calendarType->maxAssessmentCalendarsPerYear());
        $ordinal = $programmeSemester->period_in_year !== null
            ? (int) $programmeSemester->period_in_year
            : null;

        if ($ordinal === null || $ordinal < 1) {
            $taughtSemesters = $dlc->programmeSemesters
                ->filter(fn (ProgrammeSemester $ps): bool => $ps->isTaught())
                ->sortBy('position')
                ->values();

            $index = $taughtSemesters->search(
                fn (ProgrammeSemester $ps): bool => (int) $ps->id === (int) $programmeSemester->id,
            );

            if ($index === false) {
                return null;
            }

            $ordinal = ((int) $index % $periodsPerYear) + 1;
        }

        return Semester::query()
            ->where('slug', "{$calendarType->value}-{$ordinal}")
            ->first();
    }

    /**
     * Map a programme phase onto the calendar period of the same type in a given year.
     * Year 2 Sem 1 dual-writes to semester-1, not a non-existent semester-3.
     */
    public function calendarSemesterForClassConfig(
        DepartmentLevelCourse $dlc,
        ProgrammeSemester $programmeSemester,
    ): ?Semester {
        $dlc->loadMissing(['departmentLevel.level', 'programmeSemesters']);

        $calendarType = $dlc->departmentLevel?->level?->calendar_type;

        if (! $calendarType instanceof AcademicCalendarTypeEnum) {
            $calendarType = AcademicCalendarTypeEnum::tryFrom((string) $calendarType)
                ?? AcademicCalendarTypeEnum::SEMESTER;
        }

        if (! $programmeSemester->isTaught()) {
            return null;
        }

        $taughtSemesters = $dlc->programmeSemesters
            ->filter(fn (ProgrammeSemester $ps): bool => $ps->isTaught())
            ->sortBy('position')
            ->values();

        $index = $taughtSemesters->search(
            fn (ProgrammeSemester $ps): bool => (int) $ps->id === (int) $programmeSemester->id,
        );

        if ($index === false) {
            return null;
        }

        $periodsPerYear = max(1, $calendarType->maxAssessmentCalendarsPerYear());
        $ordinal = ((int) $index % $periodsPerYear) + 1;

        return Semester::query()
            ->where('slug', "{$calendarType->value}-{$ordinal}")
            ->first();
    }

    public function nextProgrammeSemester(StudentSemester $current): ?ProgrammeSemester
    {
        $current->loadMissing('enrolment');
        $enrolment = $current->enrolment;

        if (! $enrolment instanceof StudentEnrolment) {
            return null;
        }

        $currentProgrammeSemester = $this->programmeSemesterForStudentSemester($current);

        if ($currentProgrammeSemester === null) {
            return null;
        }

        $dlc = $this->resolveDepartmentLevelCourse($enrolment);

        if ($dlc === null) {
            return null;
        }

        return $dlc->programmeSemesters
            ->where('position', '>', $currentProgrammeSemester->position)
            ->sortBy('position')
            ->first();
    }

    public function nextProgrammeSemesterInSameStage(StudentSemester $current): ?ProgrammeSemester
    {
        $next = $this->nextProgrammeSemester($current);

        if ($next === null) {
            return null;
        }

        $currentProgrammeSemester = $this->programmeSemesterForStudentSemester($current);

        if ($currentProgrammeSemester === null || $currentProgrammeSemester->programme_stage_id === null) {
            return $next;
        }

        if ((int) $next->programme_stage_id !== (int) $currentProgrammeSemester->programme_stage_id) {
            return null;
        }

        return $next;
    }

    public function isLastProgrammeSemesterOfStage(StudentSemester $studentSemester): bool
    {
        $programmeSemester = $this->programmeSemesterForStudentSemester($studentSemester);

        if ($programmeSemester === null || $programmeSemester->programme_stage_id === null) {
            return $this->isLastProgrammeSemester($studentSemester);
        }

        $lastInStage = ProgrammeSemester::query()
            ->where('programme_stage_id', $programmeSemester->programme_stage_id)
            ->orderByDesc('position')
            ->first();

        return $lastInStage instanceof ProgrammeSemester
            && (int) $lastInStage->id === (int) $programmeSemester->id;
    }

    public function isLastProgrammeSemester(StudentSemester $studentSemester): bool
    {
        $programmeSemester = $this->programmeSemesterForStudentSemester($studentSemester);

        if ($programmeSemester === null) {
            return false;
        }

        $studentSemester->loadMissing('enrolment');
        $enrolment = $studentSemester->enrolment;

        if (! $enrolment instanceof StudentEnrolment) {
            return false;
        }

        $dlc = $this->resolveDepartmentLevelCourse($enrolment);

        if ($dlc === null) {
            return false;
        }

        $last = $dlc->programmeSemesters->sortByDesc('position')->first();

        return $last instanceof ProgrammeSemester
            && (int) $last->id === (int) $programmeSemester->id;
    }

    public function isCompletionProgrammeSemester(StudentSemester $studentSemester): bool
    {
        $studentSemester->loadMissing('enrolment');
        $enrolment = $studentSemester->enrolment;

        if (! $enrolment instanceof StudentEnrolment) {
            return false;
        }

        $dlc = $this->resolveDepartmentLevelCourse($enrolment);

        if ($dlc === null) {
            return false;
        }

        $programmeSemester = $this->programmeSemesterForStudentSemester($studentSemester);

        if ($programmeSemester === null) {
            return false;
        }

        $completion = $this->completionProgrammeSemesterForOffering($dlc);

        return $completion instanceof ProgrammeSemester
            && (int) $completion->id === (int) $programmeSemester->id;
    }

    public function completionProgrammeSemesterForOffering(DepartmentLevelCourse $dlc): ?ProgrammeSemester
    {
        $dlc->loadMissing('programmeSemesters');

        if ((bool) $dlc->includes_industrial_attachment && (int) $dlc->attachment_semester_count > 0) {
            $lastAttachment = $dlc->programmeSemesters
                ->filter(fn (ProgrammeSemester $ps): bool => $ps->isIndustrialAttachment())
                ->sortByDesc('position')
                ->first();

            return $lastAttachment instanceof ProgrammeSemester ? $lastAttachment : null;
        }

        $lastTaught = $dlc->programmeSemesters
            ->filter(fn (ProgrammeSemester $ps): bool => $ps->isTaught())
            ->sortByDesc('position')
            ->first();

        return $lastTaught instanceof ProgrammeSemester ? $lastTaught : null;
    }

    /**
     * The calendar slot (1-based) the student first sat this level and course in. A mid-year intake
     * starts on 2, so every one of their phases sits one slot later than the usual mapping.
     */
    public function intakeStartOrdinal(StudentEnrolment $enrolment): int
    {
        $firstCalendarId = StudentEnrolment::query()
            ->join('academic_calendars', 'academic_calendars.id', '=', 'student_enrolments.academic_calendar_id')
            ->where('student_enrolments.student_id', $enrolment->student_id)
            ->where('student_enrolments.department_level_id', $enrolment->department_level_id)
            ->where('student_enrolments.department_course_id', $enrolment->department_course_id)
            ->whereNull('academic_calendars.deleted_at')
            ->orderBy('academic_calendars.opening_date')
            ->orderBy('student_enrolments.id')
            ->value('student_enrolments.academic_calendar_id');

        $calendar = $firstCalendarId !== null ? AcademicCalendar::query()->find($firstCalendarId) : null;

        if ($calendar instanceof AcademicCalendar) {
            return max(1, $this->phaseResolver->phaseOrdinal(
                AcademicCalendarPeriodResolver::semesterSlugForCalendar($calendar),
            ));
        }

        return $this->startOrdinalForEnrolment($enrolment);
    }

    /**
     * Whether a taught phase can sit in the given calendar slot: either its usual slot, or that slot
     * shifted by the student's intake offset. Attachment phases have no slot of their own, so any fits.
     *
     * Deliberately not built on mapGlobalSemesterToProgrammeSemester(), which only ever yields Year 1.
     */
    public function phaseFitsCalendarSlot(
        DepartmentLevelCourse $dlc,
        ProgrammeSemester $programmeSemester,
        Semester $slot,
        int $startOrdinal = 1,
    ): bool {
        $dlc->loadMissing(['departmentLevel.level', 'programmeSemesters']);

        $calendarType = $dlc->departmentLevel?->level?->calendar_type;

        if (! $calendarType instanceof AcademicCalendarTypeEnum) {
            $calendarType = AcademicCalendarTypeEnum::tryFrom((string) $calendarType)
                ?? AcademicCalendarTypeEnum::SEMESTER;
        }

        if (! str_starts_with((string) $slot->slug, $calendarType->value.'-')) {
            return false;
        }

        if (! $programmeSemester->isTaught()) {
            return true;
        }

        $usual = $this->calendarSemesterForClassConfig($dlc, $programmeSemester);

        if (! $usual instanceof Semester) {
            return false;
        }

        $slotOrdinal = $this->phaseResolver->phaseOrdinal((string) $slot->slug);
        $usualOrdinal = $this->phaseResolver->phaseOrdinal((string) $usual->slug);

        if ($usualOrdinal === $slotOrdinal) {
            return true;
        }

        $periodsPerYear = max(1, $calendarType->maxAssessmentCalendarsPerYear());
        $shifted = (($usualOrdinal - 1) + (max(1, $startOrdinal) - 1)) % $periodsPerYear + 1;

        return $shifted === $slotOrdinal;
    }

    /**
     * Phases a student on this enrolment may say they are studying: the enrolment's stage (or the
     * whole offering when no stage is set), in the delivery the mode offers, plus whatever the
     * records currently show so the student can confirm it.
     *
     * @return Collection<int, ProgrammeSemester>
     */
    public function phaseOptionsForEnrolment(StudentEnrolment $enrolment): Collection
    {
        $dlc = $this->resolveDepartmentLevelCourse($enrolment);

        if ($dlc === null || $dlc->programmeSemesters->isEmpty()) {
            return collect();
        }

        $enrolment->loadMissing('modeOfStudy');
        $isOjetMode = (bool) $enrolment->modeOfStudy?->isOjet();

        $phases = $dlc->programmeSemesters
            ->filter(fn (ProgrammeSemester $phase): bool => $phase->isOfferedInMode($isOjetMode));

        if ($enrolment->programme_stage_id !== null) {
            $inStage = $phases->filter(
                fn (ProgrammeSemester $phase): bool => (int) $phase->programme_stage_id === (int) $enrolment->programme_stage_id,
            );

            if ($inStage->isNotEmpty()) {
                $phases = $inStage;
            }
        }

        $current = $enrolment->currentStudentSemester()?->programmeSemester;

        if ($current instanceof ProgrammeSemester
            && (int) $current->department_level_course_id === (int) $dlc->id
            && ! $phases->contains(fn (ProgrammeSemester $phase): bool => (int) $phase->id === (int) $current->id)) {
            $phases = $phases->push($current);
        }

        return $phases->sortBy('position')->values();
    }

    /**
     * @return list<ProgrammeSemester>
     */
    public function taughtProgrammeSemesters(DepartmentLevelCourse $dlc): array
    {
        return $dlc->programmeSemesters
            ->filter(fn (ProgrammeSemester $ps): bool => $ps->isTaught())
            ->sortBy('position')
            ->values()
            ->all();
    }
}
