<?php

declare(strict_types=1);

namespace App\Models\AccountPurge;

use App\Enums\AccountPurge\AccountPurgeArchiveStatusEnum;
use App\Enums\AccountPurge\AccountPurgeTypeEnum;
use App\Models\Students\StudentNote;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountPurgeArchive extends Model
{
    protected $fillable = [
        'tenant_id',
        'purge_type',
        'purged_by',
        'student_note_id',
        'original_user_id',
        'original_student_id',
        'summary',
        'payload',
        'payload_version',
        'purged_at',
        'flush_after',
        'flushed_at',
        'restored_at',
    ];

    protected function casts(): array
    {
        return [
            'purge_type' => AccountPurgeTypeEnum::class,
            'summary' => 'array',
            'payload' => 'array',
            'purged_at' => 'datetime',
            'flush_after' => 'datetime',
            'flushed_at' => 'datetime',
            'restored_at' => 'datetime',
        ];
    }

    public function purgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purged_by');
    }

    public function studentNote(): BelongsTo
    {
        return $this->belongsTo(StudentNote::class, 'student_note_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('flushed_at')
            ->whereNull('restored_at')
            ->whereNotNull('payload')
            ->where('payload', '!=', '[]');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeFlushed(Builder $query): Builder
    {
        return $query->whereNotNull('flushed_at');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeRestored(Builder $query): Builder
    {
        return $query->whereNotNull('restored_at');
    }

    public function isRestorable(): bool
    {
        return $this->flushed_at === null
            && $this->restored_at === null
            && $this->payload !== []
            && $this->payload !== null;
    }

    public function isFlushable(): bool
    {
        return $this->flushed_at === null && $this->restored_at === null;
    }

    public function status(): AccountPurgeArchiveStatusEnum
    {
        if ($this->flushed_at !== null) {
            return AccountPurgeArchiveStatusEnum::FLUSHED;
        }

        if ($this->restored_at !== null) {
            return AccountPurgeArchiveStatusEnum::RESTORED;
        }

        return AccountPurgeArchiveStatusEnum::ACTIVE;
    }
}
