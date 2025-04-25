<?php

namespace App\Models\Shared;

use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Address extends Model
{
   use SoftDeletes, Filterable, BelongsToTenant,Paginatable, LogsActivity;

   protected $fillable = [
		'tenant_id',
		'addressable_id',
		'addressable_type',
		'address_1',
		'address_2',
		'address_3',
		'address_4',
		'address_5',
		'address_6',
		'address_is_main',
		'meta'
	];

	public function addressable(): MorphTo
	{
		return $this->morphTo();
	}

   	public function getActivitylogOptions(): LogOptions
   	{
   		return LogOptions::defaults()
   			->logFillable()
   			->useLogName('Addresses')
   			->logOnlyDirty()
   			->dontSubmitEmptyLogs();
   	}
}
