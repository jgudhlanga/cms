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
            'succeeded_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ZBBankStatementFetchWindowFactory
    {
        return ZBBankStatementFetchWindowFactory::new();
    }
}
