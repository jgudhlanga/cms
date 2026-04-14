<?php

namespace App\Models\AcademicCalendars;

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

    protected $fillable = ['calendar_year', 'opening_date', 'closing_date'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('AcademicCalendar')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
