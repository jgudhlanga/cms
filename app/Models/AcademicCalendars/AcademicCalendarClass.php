<?php

namespace App\Models\AcademicCalendars;

use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 */
class AcademicCalendarClass extends Model
{
    use BelongsToTenant, Filterable, LogsActivity,Paginatable, SoftDeletes;

    protected $table = 'academic_calandar_classes';

    protected $fillable = ['tenant_id', 'class_config_id', 'name', 'description'];

    public function classConfig(): BelongsTo
    {
        return $this->belongsTo(ClassConfig::class, 'class_config_id');
    }

    public function studentPrograms(): HasMany
    {
        return $this->hasMany(AcademicCalendarStudentProgram::class, 'academic_calendar_class_id');
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
