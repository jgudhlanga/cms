<?php

namespace App\Models\Students;

use App\Traits\BelongsToTenant;
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
 * @method static filter(Filter $filters)
 */
class StudentProgram extends Model
{
   use HasFactory, SoftDeletes, Filterable, BelongsToTenant,Paginatable, LogsActivity;

   protected $fillable = [];

   	public function getActivitylogOptions(): LogOptions
   	{
   		return LogOptions::defaults()
   			->logFillable()
   			->useLogName('StudentProgram')
   			->logOnlyDirty()
   			->dontSubmitEmptyLogs();
   	}
}
