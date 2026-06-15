<?php

declare(strict_types=1);

namespace App\Models\Maintenance;

use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LaravelIngest\Models\IngestRun;

class StaffImportLog extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'ingest_run_id',
        'original_filename',
        'rows_total',
        'rows_succeeded',
        'rows_failed',
        'rows_skipped',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ingestRun(): BelongsTo
    {
        return $this->belongsTo(IngestRun::class);
    }
}
