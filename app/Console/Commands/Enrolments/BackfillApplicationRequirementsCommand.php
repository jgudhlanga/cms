<?php

declare(strict_types=1);

namespace App\Console\Commands\Enrolments;

use App\Services\Applications\ApplicationRequirementBackfillService;
use Illuminate\Console\Command;

class BackfillApplicationRequirementsCommand extends Command
{
    protected $signature = 'enrolments:backfill-requirements
        {--dry-run : Report what would be written without saving}
        {--fresh : Wipe existing application requirement rows before backfill}
        {--no-snapshot : Skip writing a JSON snapshot before changes}';

    protected $description = 'Backfill application_*_requirements tables from legacy department requirement tables';

    public function handle(ApplicationRequirementBackfillService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $fresh = (bool) $this->option('fresh');
        $snapshot = ! (bool) $this->option('no-snapshot');

        if ($dryRun) {
            $this->info(__('application_requirements.backfill_dry_run'));
        }

        if ($fresh && ! $dryRun) {
            $this->warn(__('application_requirements.backfill_fresh'));
        }

        try {
            $counts = $service->backfill($dryRun, $fresh, $snapshot);
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($counts['snapshot_path'] !== null) {
            $this->line(__('application_requirements.backfill_snapshot_written', [
                'path' => $counts['snapshot_path'],
            ]));
        }

        $this->info(__('application_requirements.backfill_summary', [
            'source_levels' => $counts['source_level_count'],
            'source_courses' => $counts['source_course_count'],
            'levels' => $counts['levels'],
            'courses' => $counts['courses'],
            'levels_skipped' => $counts['levels_skipped'],
            'courses_skipped' => $counts['courses_skipped'],
        ]));

        $this->info(__('application_requirements.backfill_complete'));

        return self::SUCCESS;
    }
}
