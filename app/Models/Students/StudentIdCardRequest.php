<?php

declare(strict_types=1);

namespace App\Models\Students;

use App\Enums\Students\IdCardRequestReasonEnum;
use App\Enums\Students\IdCardRequestStatusEnum;
use App\Models\Ledgers\Ledger;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class StudentIdCardRequest extends Model implements HasMedia
{
    use BelongsToTenant, Filterable, HasFactory, InteractsWithMedia, LogsActivity, Paginatable, SoftDeletes;

    public const MEDIA_COLLECTION = 'id-photo';

    protected $fillable = [
        'tenant_id',
        'student_id',
        'status',
        'reason',
        'notes',
        'photo_media_id',
        'supersedes_request_id',
        'fee_ledger_id',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'printed_by',
        'printed_at',
        'issued_by',
        'issued_at',
        'serial_number',
    ];

    protected function casts(): array
    {
        return [
            'status' => IdCardRequestStatusEnum::class,
            'reason' => IdCardRequestReasonEnum::class,
            'reviewed_at' => 'datetime',
            'printed_at' => 'datetime',
            'issued_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function printer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'photo_media_id');
    }

    public function supersededRequest(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_request_id');
    }

    public function feeLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'fee_ledger_id');
    }

    public function ledgerTransactions(): MorphMany
    {
        return $this->morphMany(Ledger::class, 'ledgerable')->withTrashed();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COLLECTION)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(257)
            ->nonQueued();

        $this->addMediaConversion('card')
            ->width(413)
            ->height(531)
            ->nonQueued();
    }

    public function photoUrl(?string $conversion = 'card'): ?string
    {
        $media = $this->photo ?? $this->getFirstMedia(self::MEDIA_COLLECTION);

        if (! $media instanceof Media) {
            return null;
        }

        if ($conversion !== null && $media->hasGeneratedConversion($conversion)) {
            return $media->getFullUrl($conversion);
        }

        return $media->getFullUrl();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('StudentIdCardRequest')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
