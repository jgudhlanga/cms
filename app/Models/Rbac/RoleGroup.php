<?php

namespace App\Models\Rbac;

use App\Http\Filters\Shared\SharedNameFilter;
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
 * @method static filter(SharedNameFilter $filters)
 */
#[ObservedBy([NameSlugObserver::class])]
class RoleGroup extends Model
{
    use SoftDeletes, Filterable, Paginatable, LogsActivity;

    protected $fillable = ['name', 'slug', 'description'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('RoleGroup')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
