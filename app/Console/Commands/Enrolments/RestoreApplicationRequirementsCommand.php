<?php

declare(strict_types=1);

namespace App\Console\Commands\Enrolments;

use App\Services\Applications\ApplicationRequirementBackfillService;
use Illuminate\Console\Command;

class RestoreApplicationRequirementsCommand extends Command
{
    protected $signature = 'enrolments:restore-requirements
        {--dry-run : Report what would be written without saving}
        {--from-snapshot= : Restore legacy tables from a backfill snapshot JSON path}';

    protected $description = 'Restore legacy department requirement tables from application requirements or a snapshot';

    public function handle(ApplicationRequirementBackfillService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $snapshot = $this->option('from-snapshot');

        if ($dryRun) {
            $this->info(__('application_requirements.restore_dry_run'));
        }

        try {
            $counts = is_string($snapshot) && $snapshot !== ''
                ? $service->restoreLegacyFromSnapshot($snapshot, $dryRun)
                : $service->restoreLegacyFromApplication($dryRun);
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(__('application_requirements.restore_summary', [
            'levels' => $counts['levels'],
            'courses' => $counts['courses'],
        ]));

        $this->info(__('application_requirements.restore_complete'));

        return self::SUCCESS;
    }
}
