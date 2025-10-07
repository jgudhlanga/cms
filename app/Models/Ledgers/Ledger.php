<?php

namespace App\Models\Ledgers;

use App\Http\Filters\Ledgers\LedgerFilter;
use App\Models\Institution\Level;
use App\Models\Shared\FeeType;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 *
 * @mixin Builder
 * @method static filter(LedgerFilter $filters)
 */
class Ledger extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, Filterable, BelongsToTenant, Paginatable, LogsActivity, InteractsWithMedia;

    protected $fillable = [
        'tenant_id',
        'ledgerable_type',
        'ledgerable_id',
        'fee_type_id',
        'payment_option',
        'type',
        'payment_status',
        'amount',
        'currency',
        'system_reference',
        'payment_reference',
        'due_date',
        'client_fee',
        'merchant_fee',
        'payment_date',
        'response_message',
        'response_code',
        'student_program_id',
        'level_id',
        'proof_of_payment_id',
        'payment_mode',
        'payment_gateway'
    ];

    public function ledgerable(): MorphTo
    {
        return $this->morphTo();
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function offerLetter(): HasOne
    {
        return $this->hasOne(Media::class, 'id', 'offer_letter_id');
    }

    public function getOfferLetterUrlAttribute(): ?string
    {
        return ($this->offer_letter_id > 0) ? $this->offerLetter->getFullUrl() : null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('receipts')->singleFile();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('Ledger')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
