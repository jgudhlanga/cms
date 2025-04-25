<?php

namespace App\Models\Departments;

use App\Http\Filters\Shared\SharedNameFilter;
use App\Traits\Paginatable;
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
class Department extends Model
{
    use HasFactory, SoftDeletes, Paginatable, LogsActivity;

    protected $fillable = ['name', 'description'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('Department')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
