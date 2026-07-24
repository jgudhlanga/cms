<?php

namespace App\Models\Rbac;

use App\Http\Filters\Rbac\ModuleFilter;
use App\Observers\Shared\TitleSlugObserver;
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
 * @mixin Builder
 *
 * @method static filter(ModuleFilter $filters)
 */
#[ObservedBy([TitleSlugObserver::class])]
class Module extends Model
{
    use Filterable, HasFactory, LogsActivity, Paginatable, SoftDeletes;

    protected $fillable = ['title', 'slug', 'description', 'status', 'settings'];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('RbacModule')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
