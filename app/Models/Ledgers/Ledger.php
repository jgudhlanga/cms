<?php

namespace App\Models\Ledgers;

use App\Http\Filters\Ledgers\LedgerFilter;
use App\Models\Institution\Level;
use App\Models\Shared\FeeType;
use App\Observers\Ledgers\LedgerObserver;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
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
 * @mixin Builder
 *
 * @method static filter(LedgerFilter $filters)
 */
#[ObservedBy([LedgerObserver::class])]
class Ledger extends Model implements HasMedia
{
    use BelongsToTenant, Filterable, HasFactory, InteractsWithMedia, LogsActivity, Paginatable, SoftDeletes;

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
        'student_application_id',
        'level_id',
        'proof_of_payment_id',
        'payment_gateway',
        'intake_period_id',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'datetime',
            'due_date' => 'datetime',
        ];
    }

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

    public function proofOfPayment(): HasOne
    {
        return $this->hasOne(Media::class, 'id', 'proof_of_payment_id');
    }

    public function getProofOfPaymentUrlAttribute(): ?string
    {
        return ($this->proof_of_payment_id > 0) ? $this->proofOfPayment->getFullUrl() : null;
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
