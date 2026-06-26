<?php

namespace App\Models\AcademicCalendars;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Traits\Paginatable;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 */
class AcademicCalendar extends Model
{
    use LogsActivity, Paginatable, SoftDeletes;

    protected $fillable = ['calendar_year', 'type', 'opening_date', 'closing_date'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AcademicCalendarTypeEnum::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('AcademicCalendar')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * @param  Builder<AcademicCalendar>  $query
     * @return Builder<AcademicCalendar>
     */
    public function scopeSemesters(Builder $query): Builder
    {
        return $query->where('type', AcademicCalendarTypeEnum::SEMESTER);
    }

    /**
     * Resolve the semester whose date range contains today, or the nearest semester when between periods.
     */
    public static function resolveSemesterForDate(?CarbonInterface $asOf = null): AcademicCalendar
    {
        $today = ($asOf ?? today())->toDateString();
        $query = static::query()->semesters();

        $current = (clone $query)
            ->whereDate('opening_date', '<=', $today)
            ->whereDate('closing_date', '>=', $today)
            ->orderBy('opening_date')
            ->first();

        if ($current instanceof AcademicCalendar) {
            return $current;
        }

        $upcoming = (clone $query)
            ->whereDate('opening_date', '>', $today)
            ->orderBy('opening_date')
            ->first();

        if ($upcoming instanceof AcademicCalendar) {
            return $upcoming;
        }

        return (clone $query)
            ->whereDate('closing_date', '<', $today)
            ->orderByDesc('closing_date')
            ->firstOrFail();
    }

    /**
     * @return Collection<int, AcademicCalendar>
     */
    public static function semestersForCalendarYear(string $calendarYear): Collection
    {
        return static::query()
            ->semesters()
            ->where('calendar_year', $calendarYear)
            ->orderBy('opening_date')
            ->get();
    }

    /**
     * @return Collection<int, AcademicCalendar>
     */
    public static function periodsForYearAndType(string $calendarYear, AcademicCalendarTypeEnum $type): Collection
    {
        return static::query()
            ->where('calendar_year', $calendarYear)
            ->where('type', $type)
            ->orderBy('opening_date')
            ->orderBy('id')
            ->get();
    }

    /**
     * Resolve the period active on a date: from opening_date until the next period's opening_date.
     */
    public static function resolveCurrentPeriodForDate(
        string $calendarYear,
        AcademicCalendarTypeEnum $type,
        ?CarbonInterface $asOf = null,
    ): ?AcademicCalendar {
        $today = ($asOf ?? Carbon::now((string) config('app.timezone')))->copy()->startOfDay();
        $periods = static::periodsForYearAndType($calendarYear, $type);

        if ($periods->isEmpty()) {
            return null;
        }

        $current = null;

        foreach ($periods as $index => $period) {
            $opening = Carbon::parse($period->opening_date)->startOfDay();

            if ($opening->gt($today)) {
                break;
            }

            $next = $periods->get($index + 1);

            if ($next === null || $today->lt(Carbon::parse($next->opening_date)->startOfDay())) {
                $current = $period;
            }
        }

        return $current;
    }

    /**
     * First period in the same year/type after the current one, or the first period in the next calendar year.
     */
    public static function resolveNextPeriodAfter(?AcademicCalendar $current): ?AcademicCalendar
    {
        if (! $current instanceof AcademicCalendar) {
            return null;
        }

        $nextInYear = static::query()
            ->where('calendar_year', $current->calendar_year)
            ->where('type', $current->type)
            ->whereDate('opening_date', '>', $current->opening_date)
            ->orderBy('opening_date')
            ->orderBy('id')
            ->first();

        if ($nextInYear instanceof AcademicCalendar) {
            return $nextInYear;
        }

        return static::query()
            ->where('type', $current->type)
            ->where('calendar_year', '>', $current->calendar_year)
            ->orderBy('calendar_year')
            ->orderBy('opening_date')
            ->orderBy('id')
            ->first();
    }

    /**
     * First period in the year/type whose opening_date is after today.
     */
    public static function resolveUpcomingPeriodForDate(
        string $calendarYear,
        AcademicCalendarTypeEnum $type,
        ?CarbonInterface $asOf = null,
    ): ?AcademicCalendar {
        $today = ($asOf ?? Carbon::now((string) config('app.timezone')))->toDateString();

        return static::query()
            ->where('calendar_year', $calendarYear)
            ->where('type', $type)
            ->whereDate('opening_date', '>', $today)
            ->orderBy('opening_date')
            ->orderBy('id')
            ->first();
    }

    /**
     * Calendars that have started (opening date before today), for a given stored year label.
     */
    public static function queryStartedForCalendarYear(string $calendarYear): Builder
    {
        return static::query()
            ->where('calendar_year', $calendarYear)
            ->whereDate('opening_date', '<', today());
    }

    /**
     * Canonical calendar for Inertia/API links: latest started period in that year.
     */
    public static function resolveCanonicalIdForCalendarYear(string $calendarYear): ?int
    {
        return static::queryStartedForCalendarYear($calendarYear)
            ->orderByDesc('opening_date')
            ->value('id');
    }

    /**
     * @return list<int>
     */
    public static function idsForStartedCalendarYear(string $calendarYear): array
    {
        return static::queryStartedForCalendarYear($calendarYear)
            ->orderByDesc('opening_date')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }
}
