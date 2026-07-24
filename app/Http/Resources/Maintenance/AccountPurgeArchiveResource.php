<?php

declare(strict_types=1);

namespace App\Http\Resources\Maintenance;

use App\Models\AccountPurge\AccountPurgeArchive;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AccountPurgeArchive */
class AccountPurgeArchiveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['studentNote', 'purgedBy']);

        $summary = is_array($this->summary) ? $this->summary : [];
        $status = $this->status();

        return [
            'type' => 'account-purge-archive',
            'id' => $this->id,
            'attributes' => [
                'purgeType' => $this->purge_type->value,
                'purgeTypeLabel' => $this->purge_type->label(),
                'status' => $status->value,
                'statusLabel' => $status->label(),
                'name' => $summary['name'] ?? null,
                'email' => $summary['email'] ?? null,
                'studentNumber' => $summary['student_number'] ?? null,
                'purgeReason' => $this->studentNote?->body,
                'purgedByName' => $this->purgedBy?->full_name,
                'purgedAt' => $this->purged_at,
                'flushAfter' => $this->flush_after,
                'flushedAt' => $this->flushed_at,
                'restoredAt' => $this->restored_at,
                'daysUntilFlush' => $this->resolveDaysUntilFlush(),
                'canRestore' => $this->isRestorable(),
                'canFlush' => $this->isFlushable(),
                'originalUserId' => $this->original_user_id,
                'originalStudentId' => $this->original_student_id,
                'archiveRetentionDays' => (int) config('purge.archive_retention_days', 30),
            ],
        ];
    }

    private function resolveDaysUntilFlush(): ?int
    {
        if ($this->flushed_at !== null || $this->restored_at !== null || $this->flush_after === null) {
            return null;
        }

        return max(0, (int) now()->diffInDays($this->flush_after, false));
    }
}
