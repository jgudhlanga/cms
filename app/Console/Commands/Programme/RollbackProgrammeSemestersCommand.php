<?php

declare(strict_types=1);

namespace App\Console\Commands\Programme;

use App\Services\Institution\RollbackProgrammeSemestersService;
use Illuminate\Console\Command;

class RollbackProgrammeSemestersCommand extends Command
{
    protected $signature = 'programme:rollback-semesters {--dry-run : Report counts without writing}';

    protected $description = 'Rollback programme semester backfill from snapshot tables';

    public function handle(RollbackProgrammeSemestersService $rollback): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $counts = $rollback->run($dryRun);

        $this->info($dryRun ? 'Dry run complete.' : 'Rollback complete.');
        foreach ($counts as $key => $count) {
            $this->line(sprintf('%s: %d', str_replace('_', ' ', $key), $count));
        }

        return self::SUCCESS;
    }
}
