<?php

declare(strict_types=1);

namespace App\Console\Commands\Maintenance;

use App\Services\Institution\CourseLevelModeService;
use Illuminate\Console\Command;

class RepairOrphanCourseLevelModesCommand extends Command
{
    protected $signature = 'maintenance:repair-orphan-course-level-modes
        {--execute : Apply the repair; without this flag the command only reports}';

    protected $description = 'Remove unused leftover course modes and restore levels that still have applications or enrolments';

    public function handle(CourseLevelModeService $service): int
    {
        $plan = $service->orphanPlan();

        if ($plan === []) {
            $this->info('No leftover course level modes found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Course', 'Level', 'Action', 'Applications/enrolments'],
            array_map(fn (array $entry): array => [
                $entry['course'],
                $entry['level'],
                $entry['action'],
                $entry['records'],
            ], $plan),
        );

        if (! $this->option('execute')) {
            $this->warn(sprintf(
                'Dry run: %d leftover row(s). Re-run with --execute to prune unused rows and restore in-use levels.',
                count($plan),
            ));

            return self::SUCCESS;
        }

        $summary = $service->repairOrphans(dryRun: false);

        $this->info(sprintf(
            'Pruned %d unused leftover row(s), restored %d in-use level(s), stripped %d unused mode(s).',
            $summary['pruned'],
            $summary['restored'],
            $summary['modes_stripped'],
        ));

        return self::SUCCESS;
    }
}
