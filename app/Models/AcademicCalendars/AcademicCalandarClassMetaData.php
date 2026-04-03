<?php

namespace App\Models\AcademicCalendars;

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
 *
 * @method static filter(Filter $filters)
 */
class AcademicCalandarClassMetaData extends Model
{
    use BelongsToTenant, Filterable, HasFactory, LogsActivity,Paginatable, SoftDeletes;

    protected $fillable = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('AcademicCalandarClassMetaData')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
