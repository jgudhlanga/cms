<?php

declare(strict_types=1);

namespace App\Services\Students\StudyPosition;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Students\StudentEnrolment;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves the period each calendar type is currently in. A period stays current from its opening
 * until the next period of that type opens, so students are not re-asked over the holidays.
 */
class CurrentStudyPeriodResolver
{
    private const CACHE_PREFIX = 'study-position:periods:';

    private const CACHE_TTL_SECONDS = 600;

    /** @var array<string, array<string, CurrentStudyPeriod>> */
    private array $memo = [];

    /**
     * @return array<string, CurrentStudyPeriod> keyed by calendar type value
     */
    public function all(?CarbonInterface $asOf = null): array
    {
        $date = ($asOf ?? Carbon::now((string) config('app.timezone')))->toDateString();

        if (isset($this->memo[$date])) {
            return $this->memo[$date];
        }

        $rows = $asOf === null
            ? Cache::remember(self::CACHE_PREFIX.$date, self::CACHE_TTL_SECONDS, fn (): array => $this->buildRows($asOf))
            : $this->buildRows($asOf);

        return $this->memo[$date] = $this->hydrate($rows);
    }

    public function forType(AcademicCalendarTypeEnum $type, ?CarbonInterface $asOf = null): ?CurrentStudyPeriod
    {
        return $this->all($asOf)[$type->value] ?? null;
    }

    public function forEnrolment(StudentEnrolment $enrolment, ?CarbonInterface $asOf = null): ?CurrentStudyPeriod
    {
        $enrolment->loadMissing('departmentLevel.level');
        $type = $enrolment->departmentLevel?->level?->calendar_type;

        return $type instanceof AcademicCalendarTypeEnum ? $this->forType($type, $asOf) : null;
    }

    public function previous(CurrentStudyPeriod $current): ?CurrentStudyPeriod
    {
        $previous = AcademicCalendar::resolvePreviousPeriodBefore($current->period);

        return $previous instanceof AcademicCalendar ? $this->build($current->type, $previous) : null;
    }

    /**
     * @return list<int>
     */
    public function currentPeriodIds(?CarbonInterface $asOf = null): array
    {
        return array_values(array_map(
            static fn (CurrentStudyPeriod $period): int => $period->periodId(),
            $this->all($asOf),
        ));
    }

    /**
     * Changes whenever any calendar type moves into a new period; used to key caches.
     */
    public function fingerprint(?CarbonInterface $asOf = null): string
    {
        $ids = $this->currentPeriodIds($asOf);
        sort($ids);

        return $ids === [] ? 'none' : implode('-', $ids);
    }

    public function forget(): void
    {
        $this->memo = [];
        Cache::forget(self::CACHE_PREFIX.Carbon::now((string) config('app.timezone'))->toDateString());
    }

    public function build(AcademicCalendarTypeEnum $type, AcademicCalendar $period): ?CurrentStudyPeriod
    {
        $slug = AcademicCalendarPeriodResolver::semesterSlugForCalendar($period);
        $slot = Semester::query()->where('slug', $slug)->first();

        if (! $slot instanceof Semester) {
            return null;
        }

        $calendarYear = (string) $period->calendar_year;
        $slotName = AcademicCalendarPeriodResolver::displayPeriodLabel($period);

        return new CurrentStudyPeriod(
            type: $type,
            period: $period,
            slot: $slot,
            calendarYear: $calendarYear,
            label: $calendarYear.' · '.$slotName,
            slotName: $slotName,
            yearPeriodIds: AcademicCalendar::periodsForYearAndType($calendarYear, $type)
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->values()
                ->all(),
        );
    }

    /**
     * Plain arrays so the cache never holds serialised models.
     *
     * @return array<string, array{periodId: int, slotId: int, yearPeriodIds: list<int>, label: string, slotName: string}>
     */
    private function buildRows(?CarbonInterface $asOf): array
    {
        $rows = [];

        foreach (AcademicCalendarTypeEnum::cases() as $type) {
            $period = AcademicCalendar::resolveLatestStartedPeriod($type, $asOf);

            if (! $period instanceof AcademicCalendar) {
                continue;
            }

            $built = $this->build($type, $period);

            if (! $built instanceof CurrentStudyPeriod) {
                continue;
            }

            $rows[$type->value] = [
                'periodId' => $built->periodId(),
                'slotId' => (int) $built->slot->id,
                'yearPeriodIds' => $built->yearPeriodIds,
                'label' => $built->label,
                'slotName' => $built->slotName,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, array{periodId: int, slotId: int, yearPeriodIds: list<int>, label: string, slotName: string}>  $rows
     * @return array<string, CurrentStudyPeriod>
     */
    private function hydrate(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $periods = AcademicCalendar::query()->whereKey(array_column($rows, 'periodId'))->get()->keyBy('id');
        $slots = Semester::query()->whereKey(array_column($rows, 'slotId'))->get()->keyBy('id');
        $hydrated = [];

        foreach ($rows as $typeValue => $row) {
            $type = AcademicCalendarTypeEnum::tryFrom((string) $typeValue);
            $period = $periods->get($row['periodId']);
            $slot = $slots->get($row['slotId']);

            if ($type === null || ! $period instanceof AcademicCalendar || ! $slot instanceof Semester) {
                continue;
            }

            $hydrated[$typeValue] = new CurrentStudyPeriod(
                type: $type,
                period: $period,
                slot: $slot,
                calendarYear: (string) $period->calendar_year,
                label: $row['label'],
                slotName: $row['slotName'],
                yearPeriodIds: $row['yearPeriodIds'],
            );
        }

        return $hydrated;
    }
}
