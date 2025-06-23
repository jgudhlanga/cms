<?php

namespace App\Models\Shared;

use App\Http\Filters\Shared\SharedNameFilter;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 *
 * @mixin Builder
 * @method static filter(SharedNameFilter $filters)
 */
class District extends Model
{
    use HasFactory, SoftDeletes, Filterable, Paginatable, LogsActivity;

    protected $fillable = ['name', 'description', 'province_id'];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('District')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
