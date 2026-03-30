<?php

namespace App\Models\Integrations\Banks;

use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 */
class ZBBankStatement extends Model
{
    use Filterable, LogsActivity, Paginatable, SoftDeletes;

    protected $table = 'zb_bank_statements';

    protected $fillable = [
        'tran_number_asc',
        'tran_number_desc',
        'transaction_id',
        'transaction_sr_id',
        'transaction_date',
        'narration',
        'reference',
        'code',
        'description',
        'debit_credit_flag',
        'amount_credit',
        'amount_debit',
        'cleared_running_balance',
        'blocked_balance',
        'debit_limit',
        'credit_limit',
        'iso_currency_code',
        'account_description',
        'ubfull_name',
        'pipe_count',
        'pipe1',
        'pipe2',
        'pipe3',
        'pipe4',
        'pipe5',
        'pipe6',
        'pipe7',
        'pipe8',
        'pipe9',
        'pipe10',
        'pipe1_details',
        'pipe2_details',
        'pipe3_details',
        'pipe4_details',
        'pipe5_details',
        'pipe6_details',
        'pipe7_details',
        'pipe8_details',
        'pipe9_details',
        'pipe10_details',
        'transaction_details',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('ZBBankStatement')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
