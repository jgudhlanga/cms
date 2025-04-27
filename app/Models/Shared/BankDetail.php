<?php

namespace App\Models\Shared;

use App\Models\Banks\Bank;
use App\Models\Banks\BankAccountType;
use App\Models\Banks\BankBranch;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BankDetail extends Model
{
	use SoftDeletes, Filterable, BelongsToTenant, Paginatable, LogsActivity;

	protected $fillable = [
		'tenant_id',
		'bankable_id',
		'bankable_type',
		'bank_id',
		'bank_branch_id',
		'bank_account_type_id',
		'bank_account_holder',
		'bank_account_number',
		'bank_account_is_main',
		'meta'
	];

	public function bankable(): MorphTo
	{
		return $this->morphTo();
	}

	public function bankAccountType(): BelongsTo
	{
		return $this->belongsTo(BankAccountType::class);
	}

	public function bank(): BelongsTo
	{
		return $this->belongsTo(Bank::class);
	}

	public function bankBranch(): BelongsTo
	{
		return $this->belongsTo(BankBranch::class);
	}

	public function getActivitylogOptions(): LogOptions
	{
		return LogOptions::defaults()
			->logFillable()
			->useLogName('BankDetail')
			->logOnlyDirty()
			->dontSubmitEmptyLogs();
	}
}
