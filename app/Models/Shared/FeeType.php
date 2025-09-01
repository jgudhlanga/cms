<?php

namespace App\Models\Shared;

use App\Http\Filters\Shared\SharedNameFilter;
use App\Observers\Shared\NameSlugObserver;
use App\Traits\AssignsPosition;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
class FeeType extends Model
{
    use HasFactory, SoftDeletes, Filterable, Paginatable, LogsActivity, AssignsPosition;

    protected $fillable = ['name', 'description', 'slug', 'position'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('FeeType')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
