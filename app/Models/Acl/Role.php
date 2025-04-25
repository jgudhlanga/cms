<?php

namespace App\Models\Acl;

use App\Http\Filters\Acl\PermissionFilter;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 *
 * @mixin Builder
 * @method static filter(PermissionFilter $filters)
 */
class Role extends SpatieRole
{
	use HasFactory, SoftDeletes, Filterable, Paginatable, LogsActivity;

	protected $fillable = ['name', 'description', 'guard_name'];

	public function getActivitylogOptions(): LogOptions
	{
		return LogOptions::defaults()
			->logFillable()
			->useLogName('Role')
			->logOnlyDirty()
			->dontSubmitEmptyLogs();
	}
}
