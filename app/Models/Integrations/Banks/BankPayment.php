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
class BankPayment extends Model
{
    use Filterable, LogsActivity,Paginatable, SoftDeletes;

    protected $fillable = [
        'transaction_id',
        'bank',
        'amount',
        'transaction_created_date',
        'narrative',
        'nr1',
        'nr2',
        'nr3',
        'nr4',
        'picked',
        'reference',
        'source',
        'status',
        'tcd',
        'transaction_date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('BankPayment')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getCurrencyAttribute(): ?string
    {
        $source = $this->source;

        if ($source === null || trim($source) === '') {
            return null;
        }

        return match ($source) {
            '4144796412405', '4144796412081' => 'USD',
            '4144796412082' => 'ZiG',
            default => null,
        };
    }
}
