<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\Institution\LevelEnum;
use App\Models\Institution\DepartmentLevel;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Support\Examinations\HexcoCourseLevelMatcher;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Works out which department level a HEXCO sitting actually belongs to.
 *
 * A statement carries its own COURSE LEVEL, and an AWARD on it closes *that* level — which
 * is frequently a level below the one the student has since moved on to. Stamping the
 * student's current enrolment onto the result (the previous behaviour) mis-files the award
 * onto the new level's first phase.
 */
class ExamResultLevelResolver
{
    /**
     * @param  string|null  $courseLevel  the statement's raw COURSE LEVEL cell, when we have it
     * @param  string|null  $session  the sitting date (ISO), used for the legacy fallback
     */
    public function resolve(
        Student $student,
        ?string $courseLevel,
        ?string $session,
        ?StudentEnrolment $currentEnrolment,
    ): ?DepartmentLevel {
        $enrolments = $this->enrolmentsFor($student);

        return $this->fromCourseLevel($courseLevel, $currentEnrolment, $enrolments)
            ?? $this->fromSessionDate($session, $enrolments)
            ?? $currentEnrolment?->departmentLevel;
    }

    /**
     * @param  Collection<int, StudentEnrolment>  $enrolments
     */
    private function fromCourseLevel(
        ?string $courseLevel,
        ?StudentEnrolment $currentEnrolment,
        Collection $enrolments,
    ): ?DepartmentLevel {
        $levelEnum = HexcoCourseLevelMatcher::match($courseLevel);

        if (! $levelEnum instanceof LevelEnum) {
            return null;
        }

        $onRecord = $enrolments->first(
            fn (StudentEnrolment $enrolment): bool => $enrolment->departmentLevel?->level?->name === $levelEnum->name(),
        );

        if ($onRecord instanceof StudentEnrolment && $onRecord->departmentLevel instanceof DepartmentLevel) {
            return $onRecord->departmentLevel;
        }

        // The prior level is usually not on this record at all — fall back to the department's
        // offering of that level, which is what the pathway card reasons about.
        $departmentId = $currentEnrolment?->institution_department_id
            ?? $enrolments->first()?->institution_department_id;

        if ($departmentId === null) {
            return null;
        }

        return DepartmentLevel::query()
            ->where('institution_department_id', $departmentId)
            ->whereHas('level', fn ($query) => $query->where('name', $levelEnum->name()))
            ->with('level')
            ->first();
    }

    /**
     * Legacy rows carry no course level. The enrolment whose academic calendar period contains
     * the sitting is a better guess than "whatever the student is enrolled on today".
     *
     * @param  Collection<int, StudentEnrolment>  $enrolments
     */
    private function fromSessionDate(?string $session, Collection $enrolments): ?DepartmentLevel
    {
        $sessionDate = $this->parseSession($session);

        if (! $sessionDate instanceof CarbonInterface) {
            return null;
        }

        $match = $enrolments->first(function (StudentEnrolment $enrolment) use ($sessionDate): bool {
            $calendar = $enrolment->academicCalendar;

            if ($calendar === null || $calendar->opening_date === null || $calendar->closing_date === null) {
                return false;
            }

            return $sessionDate->betweenIncluded(
                Carbon::parse($calendar->opening_date)->startOfDay(),
                Carbon::parse($calendar->closing_date)->endOfDay(),
            );
        });

        return $match?->departmentLevel;
    }

    private function parseSession(?string $session): ?CarbonInterface
    {
        if ($session === null || trim($session) === '') {
            return null;
        }

        try {
            return Carbon::parse($session)->startOfDay();
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @return Collection<int, StudentEnrolment>
     */
    private function enrolmentsFor(Student $student): Collection
    {
        return StudentEnrolment::query()
            ->where('student_id', $student->id)
            ->with(['departmentLevel.level', 'academicCalendar'])
            ->get();
    }
}
