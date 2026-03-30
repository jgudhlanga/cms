<?php

namespace App\Models\Integrations\Banks;

use App\Enums\Integrations\Banks\ZBBankStatementFetchWindowStatus;
use Database\Factories\ZBBankStatementFetchWindowFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Builder
 */
class ZBBankStatementFetchWindow extends Model
{
    /** @use HasFactory<ZBBankStatementFetchWindowFactory> */
    use HasFactory;

    protected $table = 'zb_bank_statement_fetch_windows';

    protected $fillable = [
        'account_type',
        'window_start',
        'window_end',
        'status',
        'attempt_count',
        'processing_started_at',
        'succeeded_at',
        'failed_at',
        'last_error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ZBBankStatementFetchWindowStatus::class,
            'window_start' => 'date',
            'window_end' => 'date',
            'processing_started_at' => 'datetime',
            'succeeded_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public static function reclaimStaleProcessing(): int
    {
        $minutes = max(1, (int) config('custom.bank-statements.processing_stale_minutes', 45));
        $threshold = now()->subMinutes($minutes);

        return self::query()
            ->where('status', ZBBankStatementFetchWindowStatus::Processing)
            ->whereNotNull('processing_started_at')
            ->where('processing_started_at', '<=', $threshold)
            ->update([
                'status' => ZBBankStatementFetchWindowStatus::Pending->value,
                'processing_started_at' => null,
            ]);
    }

    protected static function newFactory(): ZBBankStatementFetchWindowFactory
    {
        return ZBBankStatementFetchWindowFactory::new();
    }
}
