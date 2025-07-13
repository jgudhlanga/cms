<?php

namespace App\Models\Shared;

use App\Http\Filters\Shared\SharedNameFilter;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 *
 * @mixin Builder
 * @method static filter(SharedNameFilter $filters)
 */
class NextOfKin extends Model
{
    use HasFactory, SoftDeletes, Filterable, BelongsToTenant, Paginatable, LogsActivity;

    protected $fillable = ['name', 'relationship_id', 'kinnable_id', 'kinnable_type'];

    public function kinnable(): MorphTo
    {
        return $this->morphTo();
    }

    public function relationship(): BelongsTo
    {
        return $this->belongsTo(Relationship::class, 'relationship_id');
    }

    public function contacts(): MorphMany
    {
        return $this->morphMany(Contact::class, 'contactable')->withTrashed();
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable')->withTrashed();
    }


    public function firstContact(): Attribute
    {
        return Attribute::get(fn() => $this->contacts()->first());
    }

    public function firstAddress(): Attribute
    {
        return Attribute::get(fn() => $this->addresses()->first());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('NextOfKin')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
