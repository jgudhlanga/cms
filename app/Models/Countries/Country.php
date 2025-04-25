<?php

namespace App\Models\Countries;

use App\Http\Filters\Shared\SharedNameFilter;
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
 * @method static filter(SharedNameFilter $filters)
 */
class Country extends Model
{
	use HasFactory, SoftDeletes, Filterable, Paginatable, LogsActivity;

	protected $fillable = ['name', 'flag'];

	const SOUTH_AFRICAN = 163;
	const ZIMBABWEAN = 194;

	public function getActivitylogOptions(): LogOptions
	{
		return LogOptions::defaults()
			->logFillable()
			->useLogName('Country')
			->logOnlyDirty()
			->dontSubmitEmptyLogs();
	}
}
