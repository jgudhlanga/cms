<?php

namespace App\Models\AcademicCalendars;

use App\Models\Institution\IntakePeriod;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 *
 * @mixin Builder
 */
class AcademicCalendar extends Model
{
    use SoftDeletes, Paginatable, LogsActivity;

    protected $fillable = ['academic_calendar_option_id', 'calendar_year', 'opening_date', 'closing_date', 'intake_period_ids'];


    protected $casts = [
        'intake_period_ids' => 'array',
    ];

    protected $appends = ['intake_periods'];

    public function academicCalendarOption(): BelongsTo
    {
        return $this->belongsTo(AcademicCalendarOption::class, 'academic_calendar_option_id');
    }

    public function getIntakePeriodsAttribute(): Collection
    {
        return IntakePeriod::whereIn('id', $this->intake_period_ids ?? [])->get();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('AcademicCalendar')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
