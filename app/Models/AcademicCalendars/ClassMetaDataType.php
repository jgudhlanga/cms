<?php

namespace App\Models\AcademicCalendars;

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
class ClassMetaDataType extends Model
{
    use Filterable, LogsActivity ,Paginatable, SoftDeletes;

    protected $fillable = ['name', 'description'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('ClassMetaDataType')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
