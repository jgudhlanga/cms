<?php

declare(strict_types=1);

namespace App\Services\Institution;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Institution\ProgrammeSemesterKindEnum;
use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
use App\Services\Students\StudentSemesterPhaseResolver;
use Illuminate\Support\Collection;

class ProgrammeSemesterResolver
{
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

        return DepartmentLevelCourse::query()
            ->where('department_level_id', $departmentLevelId)
            ->where('department_course_id', $departmentCourseId)
            ->with(['programmeSemesters', 'departmentLevel.level'])
            ->first();
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

        return $this->mapGlobalSemesterToProgrammeSemester($dlc, (int) $studentSemester->semester_id);
    }

    public function mapGlobalSemesterToProgrammeSemester(
        DepartmentLevelCourse $dlc,
        int $globalSemesterId,
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

        /** @var ProgrammeSemester|null $match */
        $match = $programmeSemesters
            ->where('kind', ProgrammeSemesterKindEnum::TAUGHT)
            ->sortBy('position')
            ->values()
            ->get($ordinal - 1);

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

        $ordinal = (int) $index + 1;

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
