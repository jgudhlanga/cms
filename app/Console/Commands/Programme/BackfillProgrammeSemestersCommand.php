<?php

declare(strict_types=1);

namespace App\Console\Commands\Programme;

use App\Services\Institution\BackfillProgrammeSemestersService;
use Illuminate\Console\Command;

class BackfillProgrammeSemestersCommand extends Command
{
    protected $signature = 'programme:backfill-semesters {--dry-run : Report counts without writing} {--fresh : Re-snapshot DLC rows when rollback tables are empty}';

    protected $description = 'Backfill programme semester structure and map inclusions, configs, and modules';

    public function handle(BackfillProgrammeSemestersService $backfill): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $fresh = (bool) $this->option('fresh');

        try {
            $counts = $backfill->run($dryRun, $fresh);
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($dryRun ? 'Dry run complete.' : 'Backfill complete.');
        foreach ($counts as $key => $count) {
            $this->line(sprintf('%s: %d', str_replace('_', ' ', $key), $count));
        }

        return self::SUCCESS;
    }
}
