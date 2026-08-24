<?php

declare(strict_types=1);

namespace App\Console\Commands\Enrolments;

use App\Services\Applications\ApplicationOfferingBackfillService;
use Illuminate\Console\Command;

class BackfillApplicationOfferingsCommand extends Command
{
    protected $signature = 'enrolments:backfill-offerings
        {--dry-run : Report what would be written without saving}
        {--fresh : Wipe existing application offering rows before backfill}
        {--no-snapshot : Skip writing a JSON snapshot before changes}';

    protected $description = 'Backfill application_offering_* tables from legacy department application flags and course_level_modes';

    public function handle(ApplicationOfferingBackfillService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $fresh = (bool) $this->option('fresh');
        $snapshot = ! (bool) $this->option('no-snapshot');

        if ($dryRun) {
            $this->info(__('application_offerings.backfill_dry_run'));
        }

        if ($fresh && ! $dryRun) {
            $this->warn(__('application_offerings.backfill_fresh'));
        }

        try {
            $counts = $service->backfill($dryRun, $fresh, $snapshot);
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($counts['snapshot_path'] !== null) {
            $this->line(__('application_offerings.backfill_snapshot_written', [
                'path' => $counts['snapshot_path'],
            ]));
        }

        $this->info(__('application_offerings.backfill_summary', [
            'departments' => $counts['departments'],
            'departments_skipped' => $counts['departments_skipped'],
            'levels' => $counts['levels'],
            'courses' => $counts['courses'],
            'modes' => $counts['modes'],
            'courses_skipped' => $counts['courses_skipped'],
        ]));

        $this->info(__('application_offerings.backfill_complete'));

        return self::SUCCESS;
    }
}
