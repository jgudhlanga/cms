<?php

namespace App\Models\Acl;

use App\Http\Filters\Acl\PermissionFilter;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 *
 * @mixin Builder
 * @method static filter(PermissionFilter $filters)
 */
class Permission extends SpatiePermission
{
	use HasFactory, SoftDeletes, Filterable, Paginatable, LogsActivity;

	protected $fillable = ['name', 'guard_name', 'description', 'module_id'];

	public function module(): BelongsTo
	{
		return $this->belongsTo(Module::class);
	}

	public function getActivitylogOptions(): LogOptions
	{
		return LogOptions::defaults()
			->logFillable()
			->useLogName('Permission')
			->logOnlyDirty()
			->dontSubmitEmptyLogs();
	}
}
