<?php

namespace App\Models\Acl;

use App\Http\Filters\Acl\RoleFilter;
use App\Observers\Shared\NameSlugObserver;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 *
 * @mixin Builder
 * @method static filter(RoleFilter $filters)
 */
#[ObservedBy([NameSlugObserver::class])]
class Role extends SpatieRole
{
    use HasFactory, SoftDeletes, Filterable, Paginatable, LogsActivity;

    protected $fillable = ['name', 'slug', 'description', 'guard_name', 'role_group_id'];

    public function roleGroup(): BelongsTo
    {
        return $this->belongsTo(RoleGroup::class);
    }



    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('Role')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
