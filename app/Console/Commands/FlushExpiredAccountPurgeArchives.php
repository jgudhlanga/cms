<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AccountPurge\AccountPurgeArchiveFlushService;
use Illuminate\Console\Command;

class FlushExpiredAccountPurgeArchives extends Command
{
    protected $signature = 'account-purge-archives:flush-expired';

    protected $description = 'Permanently flush expired account purge archives';

    public function handle(AccountPurgeArchiveFlushService $flushService): int
    {
        $count = $flushService->flushExpired();

        $this->info("Flushed {$count} expired account purge archive(s).");

        return self::SUCCESS;
    }
}
