<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Students\StudentExamResultComment;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Students\StudentExamResult;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ExamResultEnrolmentStatusResolver
{
    /**
     * Resolve semester-matched exam metadata keyed by semester slug.
     *
     * @return Collection<string, array{comment: StudentExamResultComment|null, session: string, candidateNumber: string}>
     */
    public function resolveMetadataForLevel(
        int $studentId,
        ?int $departmentLevelId,
        string $calendarYear,
        AcademicCalendarTypeEnum $calendarType,
    ): Collection {
        if ($departmentLevelId === null) {
            return collect();
        }

        $examResults = StudentExamResult::query()
            ->where('student_id', $studentId)
            ->where('calendar_year', (int) $calendarYear)
            ->where(function ($query) use ($departmentLevelId): void {
                $query->where('department_level_id', $departmentLevelId)
                    ->orWhereNull('department_level_id');
            })
            ->get();

        if ($examResults->isEmpty()) {
            return collect();
        }

        $calendars = AcademicCalendar::periodsForYearAndType($calendarYear, $calendarType);

        if ($calendars->isEmpty()) {
            return collect();
        }

        $result = collect();

        foreach ($examResults as $examResult) {
            $sessionDate = $this->parseSessionDate($examResult->session);

            if ($sessionDate === null) {
                continue;
            }

            $matchedSlug = $this->matchSessionToSemesterSlug($sessionDate, $calendars);

            if ($matchedSlug !== null) {
                $result->put($matchedSlug, [
                    'comment' => $examResult->comment instanceof StudentExamResultComment ? $examResult->comment : null,
                    'session' => (string) $examResult->session,
                    'candidateNumber' => (string) $examResult->candidate_number,
                ]);
            }
        }

        return $result;
    }

    /**
     * Pre-load all exam results for a student + department level + calendar year,
     * keyed by the semester slug they map to.
     *
     * @return Collection<string, StudentExamResultComment> keyed by semester slug
     */
    public function resolveForLevel(
        int $studentId,
        ?int $departmentLevelId,
        string $calendarYear,
        AcademicCalendarTypeEnum $calendarType,
    ): Collection {
        return $this->resolveMetadataForLevel($studentId, $departmentLevelId, $calendarYear, $calendarType)
            ->map(fn (array $metadata): ?StudentExamResultComment => $metadata['comment'])
            ->filter();
    }

    /**
     * Resolve exam session strings keyed by semester slug for a student + level + year.
     *
     * @return Collection<string, string> keyed by semester slug, value is the raw session string
     */
    public function resolveSessionsForLevel(
        int $studentId,
        ?int $departmentLevelId,
        string $calendarYear,
        AcademicCalendarTypeEnum $calendarType,
    ): Collection {
        return $this->resolveMetadataForLevel($studentId, $departmentLevelId, $calendarYear, $calendarType)
            ->map(fn (array $metadata): string => $metadata['session']);
    }

    private function parseSessionDate(?string $session): ?Carbon
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
     * @param  Collection<int, AcademicCalendar>  $calendars  sorted by opening_date asc
     */
    private function matchSessionToSemesterSlug(Carbon $sessionDate, Collection $calendars): ?string
    {
        foreach ($calendars as $index => $calendar) {
            $opening = Carbon::parse($calendar->opening_date)->startOfDay();
            $closing = Carbon::parse($calendar->closing_date)->endOfDay();

            if ($sessionDate->gte($opening) && $sessionDate->lte($closing)) {
                return AcademicCalendarPeriodResolver::semesterSlugForCalendar($calendar);
            }
        }

        // Fallback: find the calendar whose closing_date is closest before the session
        $closest = null;
        $closestDiff = PHP_INT_MAX;

        foreach ($calendars as $calendar) {
            $closing = Carbon::parse($calendar->closing_date)->endOfDay();
            $diff = $sessionDate->diffInDays($closing);

            if ($sessionDate->gte($closing) && $diff < $closestDiff) {
                $closestDiff = $diff;
                $closest = $calendar;
            }
        }

        return $closest !== null
            ? AcademicCalendarPeriodResolver::semesterSlugForCalendar($closest)
            : null;
    }
}
