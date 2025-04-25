<?php

namespace App\Models\Tenants;

use App\Http\Filters\Tenant\TenantFilter;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Database\Factories\Tenants\TenantFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 *
 * @mixin Builder
 * @method static filter(TenantFilter $filters)
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, SoftDeletes, Filterable, Paginatable, LogsActivity;

	protected $fillable = ['name', 'meta', 'is_active'];
	public function getActivitylogOptions(): LogOptions
	{
		return LogOptions::defaults()
			->logFillable()
			->useLogName('Tenant')
			->logOnlyDirty()
			->dontSubmitEmptyLogs();
		// Chain fluent methods for configuration options
	}
}
