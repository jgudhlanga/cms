<?php

namespace App\Models\Examinations;

use App\Enums\Examinations\ExaminationImportSourceEnum;
use App\Enums\Examinations\ExaminationImportStatusEnum;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use App\Traits\Paginatable;
use Database\Factories\Examinations\ExaminationImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExaminationImport extends Model
{
    /** @use HasFactory<ExaminationImportFactory> */
    use BelongsToTenant, HasFactory, Paginatable;

    protected $fillable = [
        'tenant_id',
        'source',
        'status',
        'original_filename',
        'stored_path',
        'rows_total',
        'rows_processed',
        'rows_upserted',
        'rows_failed',
        'error_message',
        'started_by',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'source' => ExaminationImportSourceEnum::class,
            'status' => ExaminationImportStatusEnum::class,
            'rows_total' => 'integer',
            'rows_processed' => 'integer',
            'rows_upserted' => 'integer',
            'rows_failed' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExaminationResult::class);
    }

    public function progressPercent(): int
    {
        if ($this->rows_total <= 0) {
            return $this->status === ExaminationImportStatusEnum::Completed ? 100 : 0;
        }

        return (int) min(100, round(($this->rows_processed / $this->rows_total) * 100));
    }
}
