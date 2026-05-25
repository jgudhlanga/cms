<?php

namespace App\Models\HMS;

use App\Models\Institution\Staff;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 *
 * @mixin Builder
 */
class Hostel extends Model
{
   use SoftDeletes, Filterable, BelongsToTenant,Paginatable, LogsActivity;

   protected $fillable = [
		'name',
		'tenant_id',
		'warden_id', 
		'location', 
		'floor_count', 
		'rooms_count', 
		'capacity', 
		'status',
		'type', 
		'description',
];

   	public function getActivitylogOptions(): LogOptions
   	{
   		return LogOptions::defaults()
   			->logFillable()
   			->useLogName('Hostel')
   			->logOnlyDirty()
   			->dontSubmitEmptyLogs();
   	}

    public function warden(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'warden_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(HostelRoom::class);
    }
}
