<?php

namespace App\Models\AcademicCalendars;

use App\Models\Institution\Staff;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    use BelongsToTenant, Filterable, LogsActivity, Paginatable, SoftDeletes;

    protected $table = 'academic_calendar_class_meta_data';

    protected $fillable = [
        'tenant_id',
        'academic_calendar_class_id',
        'staff_id',
        'class_metadata_type_id',
        'metadatable_type',
        'metadatable_id',
    ];

    public function academicCalendarClass(): BelongsTo
    {
        return $this->belongsTo(AcademicCalendarClass::class, 'academic_calendar_class_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function classMetadataType(): BelongsTo
    {
        return $this->belongsTo(ClassMetaDataType::class, 'class_metadata_type_id');
    }

    public function metadatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('AcademicCalendarClassMetaData')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
