<?php

namespace App\Models\AcademicCalendars;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Traits\Paginatable;
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
