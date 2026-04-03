<?php

namespace App\Models\AcademicCalendars;

use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 */
class AcademicCalandarClass extends Model
{
    use BelongsToTenant, Filterable, LogsActivity,Paginatable, SoftDeletes;

    protected $fillable = ['tenant_id', 'academic_calendar_class_config_id', 'name', 'description'];

    public function academicCalendarClassConfig(): BelongsTo
    {
        return $this->belongsTo(AcademicCalendarClassConfig::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('AcademicCalandarClass')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
