<?php

namespace App\Models\AcademicCalendars;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Http\Filters\AcademicCalendars\AcademicCalendarFilter;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 * @method static filter(AcademicCalendarFilter $filters)
 */
class AcademicCalendar extends Model
{
    use SoftDeletes, Filterable, BelongsToTenant, Paginatable, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'opening_date',
        'closing_date',
        'description',
    ];

    protected $casts = [
        'type' => AcademicCalendarTypeEnum::class,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('AcademicCalendar')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
