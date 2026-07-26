<?php

namespace App\Models\AcademicCalendars;

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
 * @mixin Builder
 *
 * @method static filter(SharedNameFilter $filters)
 */
#[ObservedBy([NameSlugObserver::class])]
class Semester extends Model
{
    use Filterable, LogsActivity, Paginatable, SoftDeletes;

    protected $table = 'semesters';

    protected $fillable = ['name', 'description', 'slug'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('Semester')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
