<?php

namespace App\Models\Ledgers;

use App\Http\Filters\Ledgers\LedgerFilter;
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
 * @method static filter(LedgerFilter $filters)
 */
class Ledger extends Model
{
   use HasFactory, SoftDeletes, Filterable, BelongsToTenant,Paginatable, LogsActivity;

   protected $fillable = [
       'tenant_id',
       'ledgerable_type',
       'ledgerable_id',
       'fee_type_id',
       'payment_method_id',
       'type',
       'payment_status',
       'amount',
       'system_reference',
       'payment_reference',
       'due_date',
       'payment_date'
   ];

   	public function getActivitylogOptions(): LogOptions
   	{
   		return LogOptions::defaults()
   			->logFillable()
   			->useLogName('Ledger')
   			->logOnlyDirty()
   			->dontSubmitEmptyLogs();
   	}
}
