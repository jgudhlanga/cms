<?php

namespace App\Http\Resources\Examinations;

use App\Models\Examinations\ExaminationImport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ExaminationImport
 */
class ExaminationImportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source->value,
            'sourceLabel' => $this->source->label(),
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'originalFilename' => $this->original_filename,
            'rowsTotal' => $this->rows_total,
            'rowsProcessed' => $this->rows_processed,
            'rowsUpserted' => $this->rows_upserted,
            'rowsFailed' => $this->rows_failed,
            'progressPercent' => $this->progressPercent(),
            'errorMessage' => $this->error_message,
            'startedBy' => $this->starter === null ? null : [
                'id' => $this->starter->id,
                'name' => $this->starter->full_name,
                'email' => $this->starter->email,
            ],
            'startedAt' => $this->started_at?->toIso8601String(),
            'completedAt' => $this->completed_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
