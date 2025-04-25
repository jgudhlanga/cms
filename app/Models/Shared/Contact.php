<?php

namespace App\Models\Shared;

use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Contact extends Model
{
	use SoftDeletes, Filterable, BelongsToTenant, Paginatable, LogsActivity;

	protected $fillable = [
		'tenant_id',
		'contactable_id',
		'contactable_type',
		'name',
		'phone_number',
		'alt_phone_number',
		'email_address',
		'alt_email_address',
		'contact_is_main',
		'meta',
	];

	public function contactable(): MorphTo
	{
		return $this->morphTo();
	}

	public function getActivitylogOptions(): LogOptions
	{
		return LogOptions::defaults()
			->logFillable()
			->useLogName('Contact')
			->logOnlyDirty()
			->dontSubmitEmptyLogs();
	}
}

