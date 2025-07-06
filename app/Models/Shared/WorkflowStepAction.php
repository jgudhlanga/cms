<?php

namespace App\Models\Shared;

use App\Http\Filters\Shared\SharedTitleFilter;
use App\Observers\Shared\NameSlugObserver;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 *
 * @mixin Builder
 * @method static filter(SharedTitleFilter $filters)
 */
#[ObservedBy([NameSlugObserver::class])]
class WorkflowStepAction extends Model
{
    use SoftDeletes, Filterable, Paginatable, LogsActivity;

    protected $fillable = ['name', 'title'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('WorkflowStepAction')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
