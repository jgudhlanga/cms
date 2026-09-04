<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class StudentSemesterPhaseResolver
{
    /**
     * @return Collection<int, Semester>
     */
    public function phaseOptions(AcademicCalendarTypeEnum $type): Collection
    {
        $prefix = $type->value;

        return Semester::query()
            ->where('slug', 'like', "{$prefix}-%")
            ->get()
            ->sortBy(function (Semester $option): int {
                $parts = explode('-', (string) $option->slug);

                return (int) end($parts);
            })
            ->values();
    }

    /**
     * @return list<Semester>
     */
    public function phasesToCreateForEnrolment(
        StudentEnrolment $enrolment,
        ?CarbonInterface $asOf = null,
        ?int $sourceSemesterId = null,
    ): array {
        $enrolment->loadMissing(['academicCalendar', 'departmentLevel.level']);

        $calendarType = $enrolment->departmentLevel?->level?->calendar_type;
        $calendarYear = $enrolment->academicCalendar?->calendar_year;

        if (! $calendarType instanceof AcademicCalendarTypeEnum || ! is_string($calendarYear) || $calendarYear === '') {
            return [];
        }

        $allPhases = $this->phaseOptions($calendarType)->all();

        if ($allPhases === []) {
            return [];
        }

        $sourceSemesterId ??= $enrolment->semester_id !== null ? (int) $enrolment->semester_id : null;
        $yearRelation = $this->yearRelation($calendarYear, $calendarType, $asOf);

        $startOrdinal = $this->startOrdinalForEnrolment($enrolment, $sourceSemesterId);

        $phases = match ($yearRelation) {
            'past' => $allPhases,
            'future' => [$allPhases[0]],
            default => $this->phasesUpToCurrentSlug($allPhases, $calendarYear, $calendarType, $asOf),
        };

        $phases = $this->extendPhasesToSourceSemester($phases, $allPhases, $sourceSemesterId);

        $withinWindow = array_values(array_filter(
            $phases,
            fn (Semester $phase): bool => $this->phaseOrdinal((string) $phase->slug) >= $startOrdinal,
        ));

        if ($withinWindow !== []) {
            return $withinWindow;
        }

        // A future-year enrolment starting mid-year: keep its own start phase rather than
        // falling back to phase 1, which it would never sit.
        $startPhase = collect($allPhases)->first(
            fn (Semester $phase): bool => $this->phaseOrdinal((string) $phase->slug) === $startOrdinal,
        );

        return $startPhase instanceof Semester ? [$startPhase] : $phases;
    }

    /**
     * The earliest phase this enrolment may hold. Phases already on the record pin the floor,
     * so a February student whose enrolment later drifts onto the second calendar keeps their
     * semester-1 row; a fresh August enrolment starts at semester-2 instead of being backfilled
     * with a semester-1 phase it never sat.
     */
    private function startOrdinalForEnrolment(StudentEnrolment $enrolment, ?int $sourceSemesterId): int
    {
        // Queried rather than eager-loaded: this runs before the phases are written, and
        // priming the relation here would leave the caller holding an empty cached collection.
        $existing = StudentSemester::query()
            ->where('student_semesters.student_enrolment_id', $enrolment->id)
            ->join('semesters', 'semesters.id', '=', 'student_semesters.semester_id')
            ->pluck('semesters.slug')
            ->map(fn ($slug): int => $this->phaseOrdinal((string) $slug))
            ->filter(fn (int $ordinal): bool => $ordinal > 0);

        if ($existing->isNotEmpty()) {
            return (int) $existing->min();
        }

        if ($sourceSemesterId !== null) {
            $source = Semester::query()->find($sourceSemesterId);

            if ($source instanceof Semester) {
                return $this->phaseOrdinal((string) $source->slug);
            }
        }

        $calendar = $enrolment->academicCalendar;

        if ($calendar === null) {
            return 1;
        }

        return $this->phaseOrdinal(AcademicCalendarPeriodResolver::semesterSlugForCalendar($calendar));
    }

    public function phaseOrdinal(string $slug): int
    {
        $parts = explode('-', $slug);

        return (int) end($parts);
    }

    /**
     * @param  list<Semester>  $allPhases
     * @return list<Semester>
     */
    private function phasesUpToCurrentSlug(
        array $allPhases,
        string $calendarYear,
        AcademicCalendarTypeEnum $type,
        ?CarbonInterface $asOf,
    ): array {
        $currentSlug = AcademicCalendarPeriodResolver::currentSemesterSlugForYear($calendarYear, $type, $asOf);
        $currentOrdinal = $this->phaseOrdinal($currentSlug);

        return array_values(array_filter(
            $allPhases,
            fn (Semester $phase): bool => $this->phaseOrdinal((string) $phase->slug) <= $currentOrdinal,
        ));
    }

    /**
     * @param  list<Semester>  $phases
     * @param  list<Semester>  $allPhases
     * @return list<Semester>
     */
    private function extendPhasesToSourceSemester(array $phases, array $allPhases, ?int $sourceSemesterId): array
    {
        if ($sourceSemesterId === null || $phases === [] || $allPhases === []) {
            return $phases;
        }

        $sourcePhase = collect($allPhases)->first(
            fn (Semester $phase): bool => (int) $phase->id === $sourceSemesterId,
        );

        if (! $sourcePhase instanceof Semester) {
            return $phases;
        }

        $targetOrdinal = $this->phaseOrdinal((string) $sourcePhase->slug);
        $lastPhase = $phases[array_key_last($phases)] ?? null;
        $lastOrdinal = $lastPhase instanceof Semester
            ? $this->phaseOrdinal((string) $lastPhase->slug)
            : 0;

        if ($targetOrdinal <= $lastOrdinal) {
            return $phases;
        }

        return array_values(array_filter(
            $allPhases,
            fn (Semester $phase): bool => $this->phaseOrdinal((string) $phase->slug) <= $targetOrdinal,
        ));
    }

    private function yearRelation(
        string $enrolmentCalendarYear,
        AcademicCalendarTypeEnum $type,
        ?CarbonInterface $asOf,
    ): string {
        $asOf = $asOf ?? Carbon::now((string) config('app.timezone'));
        $today = $asOf->copy()->timezone((string) config('app.timezone'))->toDateString();

        $currentCalendar = AcademicCalendar::query()
            ->where('type', $type->value)
            ->whereDate('opening_date', '<=', $today)
            ->whereDate('closing_date', '>=', $today)
            ->orderBy('opening_date')
            ->first();

        if ($currentCalendar === null) {
            $latestStarted = AcademicCalendar::query()
                ->where('type', $type->value)
                ->whereDate('opening_date', '<', $today)
                ->orderByDesc('opening_date')
                ->first();

            if ($latestStarted === null) {
                return 'future';
            }

            $currentYear = (string) $latestStarted->calendar_year;

            return strcmp($enrolmentCalendarYear, $currentYear) < 0 ? 'past' : 'future';
        }

        $currentYear = (string) $currentCalendar->calendar_year;

        if ($enrolmentCalendarYear === $currentYear) {
            return 'current';
        }

        return strcmp($enrolmentCalendarYear, $currentYear) < 0 ? 'past' : 'future';
    }
}
