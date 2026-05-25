<?php

namespace App\Models\AcademicCalendars;

use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 *
 * @method static filter(Filter $filters)
 */
class AcademicCalendarClassMetaData extends Model
{
    use BelongsToTenant, Filterable, LogsActivity,Paginatable, SoftDeletes;

    protected $table = 'academic_calandar_class_meta_data';

    protected $fillable = ['tenant_id', 'class_metadata_type_id', 'metadatable_type', 'metadatable_id'];

    public function metadatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('AcademicCalandarClassMetaData')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
