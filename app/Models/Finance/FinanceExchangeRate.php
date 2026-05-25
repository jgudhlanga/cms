<?php

namespace App\Models\Finance;

use App\Traits\Filterable;
use App\Traits\Paginatable;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 */
class FinanceExchangeRate extends Model
{
    use Filterable, LogsActivity,Paginatable, SoftDeletes;

    protected $fillable = [
        'date',
        'currency_from',
        'currency_to',
        'rate',
    ];

    public function setDateAttribute(mixed $value): void
    {
        if ($value === null) {
            $this->attributes['date'] = null;

            return;
        }

        if (is_string($value) && trim($value) === '') {
            $this->attributes['date'] = null;

            return;
        }

        if ($value instanceof DateTimeInterface) {
            $date = Carbon::instance($value);
        } else {
            $raw = trim((string) $value);

            $date = match (true) {
                preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1 => Carbon::createFromFormat('Y-m-d', $raw),
                preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $raw) === 1 => Carbon::createFromFormat('Y/m/d', $raw),
                preg_match('/^\d{2}-\d{2}-\d{4}$/', $raw) === 1 => Carbon::createFromFormat('d-m-Y', $raw),
                preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $raw) === 1 => Carbon::createFromFormat('d/m/Y', $raw),
                default => Carbon::parse($raw),
            };
        }

        $this->attributes['date'] = $date->format('Y-m-d');
    }

    public function setRateAttribute(mixed $value): void
    {
        if ($value === null) {
            $this->attributes['rate'] = null;

            return;
        }

        // Preserve exact decimal digits (including trailing zeros) for string inputs.
        if (is_string($value)) {
            $this->attributes['rate'] = trim($value);

            return;
        }

        $this->attributes['rate'] = trim((string) $value);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('FinanceExchangeRate')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
