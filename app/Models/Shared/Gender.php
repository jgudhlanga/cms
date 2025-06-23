<?php

namespace App\Models\Shared;

use App\Http\Filters\Shared\SharedTitleFilter;
use App\Traits\Filterable;
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
 * @method static filter(SharedTitleFilter $filters)
 */
class Gender extends Model
{
	use HasFactory, SoftDeletes, Filterable, Paginatable, LogsActivity;

	protected $fillable = ['title', 'description'];

	public function getActivitylogOptions(): LogOptions
	{
		return LogOptions::defaults()
			->logFillable()
			->useLogName('Gender')
			->logOnlyDirty()
			->dontSubmitEmptyLogs();
	}
}
