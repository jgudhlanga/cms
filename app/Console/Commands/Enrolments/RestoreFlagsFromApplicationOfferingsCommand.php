<?php

declare(strict_types=1);

namespace App\Console\Commands\Enrolments;

use App\Services\Applications\ApplicationOfferingBackfillService;
use Illuminate\Console\Command;

class RestoreFlagsFromApplicationOfferingsCommand extends Command
{
    protected $signature = 'enrolments:restore-flags-from-offerings
        {--dry-run : Report what would be written without saving}
        {--from-snapshot= : Restore legacy flags from a backfill snapshot JSON path}';

    protected $description = 'Restore legacy show_on_current_application_period / has_apprentice_courses flags from application offerings (or a snapshot)';

    public function handle(ApplicationOfferingBackfillService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $snapshot = $this->option('from-snapshot');

        if ($dryRun) {
            $this->info(__('application_offerings.restore_dry_run'));
        }

        try {
            $counts = is_string($snapshot) && $snapshot !== ''
                ? $service->restoreFlagsFromSnapshot($snapshot, $dryRun)
                : $service->restoreFlagsFromOfferings($dryRun);
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(__('application_offerings.restore_summary', [
            'departments' => $counts['departments'],
            'levels' => $counts['levels'],
            'courses' => $counts['courses'],
        ]));

        $this->info(__('application_offerings.restore_complete'));

        return self::SUCCESS;
    }
}
