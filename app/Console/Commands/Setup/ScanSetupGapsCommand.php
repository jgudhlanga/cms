<?php

declare(strict_types=1);

namespace App\Console\Commands\Setup;

use App\Services\Setup\SetupGapNotifier;
use App\Services\Setup\SetupGapScanner;
use Illuminate\Console\Command;

class ScanSetupGapsCommand extends Command
{
    protected $signature = 'setup-gaps:scan
        {--quiet-notifications : Record the gaps without notifying anyone}';

    protected $description = 'Check departments for configuration gaps and raise in-app alerts for the ones found';

    public function handle(SetupGapScanner $scanner, SetupGapNotifier $notifier): int
    {
        $summary = $scanner->scan();

        $this->table(
            ['Raised', 'Reopened', 'Resolved', 'Found'],
            [[$summary['raised'], $summary['reopened'], $summary['resolved'], $summary['seen']]],
        );

        if ($summary['failed'] !== []) {
            $this->warn(sprintf(
                'Skipped %d check(s) that failed: %s. Their existing gaps were left open.',
                count($summary['failed']),
                implode(', ', $summary['failed']),
            ));
        }

        if ($this->option('quiet-notifications')) {
            $this->info('Notifications suppressed.');

            return self::SUCCESS;
        }

        $notified = $notifier->notifyPending();

        $this->info("Announced {$notified} setup issue(s).");

        return self::SUCCESS;
    }
}
