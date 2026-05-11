<?php

namespace App\Models\HMS;

use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 *
 * @mixin Builder
 */
class HostelRoom extends Model
{
   use SoftDeletes, Filterable, BelongsToTenant,Paginatable, LogsActivity;

   protected $fillable = [
	'name',
	'hostel_id',
	'room_type',
	'capacity',
	'status',
	'tenant_id',
	'max_occupancy',
	'floor_number',
	'description'
   ];

   public function hostel(): BelongsTo
   {
	   return $this->belongsTo(Hostel::class);
   }

   	public function getActivitylogOptions(): LogOptions
   	{
   		return LogOptions::defaults()
   			->logFillable()
   			->useLogName('HostelRoom')
   			->logOnlyDirty()
   			->dontSubmitEmptyLogs();
   	}
}
