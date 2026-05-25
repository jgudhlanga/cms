<?php

namespace App\Models\AcademicCalendars;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
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
