<?php

namespace App\Models\AcademicCalendars;

use App\Http\Filters\Shared\SharedNameFilter;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 *
 * @mixin Builder
 * @method static filter(SharedNameFilter $filters)
 */
class AcademicCalendarOption extends Model
{
    use SoftDeletes, Filterable, Paginatable, LogsActivity;

    protected $fillable = ['name', 'description', 'calendar_type'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('AcademicCalendarOption')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
