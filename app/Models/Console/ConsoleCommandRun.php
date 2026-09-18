<?php

declare(strict_types=1);

namespace App\Models\Console;

use App\Enums\Console\ConsoleRunStatusEnum;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 *
 * @property int $id
 * @property int|null $tenant_id
 * @property string $uuid
 * @property string $command_key
 * @property string $signature
 * @property array<string, mixed>|null $parameters
 * @property ConsoleRunStatusEnum $status
 * @property int|null $queued_by_user_id
 * @property Carbon|null $queued_at
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property int|null $duration_ms
 * @property int|null $exit_code
 * @property string|null $output
 * @property bool $output_truncated
 * @property string|null $error
 * @property string|null $ip_address
 * @property User|null $queuedBy
 */
class ConsoleCommandRun extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'uuid',
        'command_key',
        'signature',
        'parameters',
        'status',
        'queued_by_user_id',
        'queued_at',
        'started_at',
        'finished_at',
        'duration_ms',
        'exit_code',
        'output',
        'output_truncated',
        'error',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'status' => ConsoleRunStatusEnum::class,
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'output_truncated' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function queuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'queued_by_user_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['command_key', 'signature', 'parameters', 'status', 'exit_code'])
            ->useLogName('Console')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
