<?php

declare(strict_types=1);

namespace App\Console\Commands\Maintenance;

use App\Services\Maintenance\Institution\DepartmentProgrammeLinkRepairService;
use Illuminate\Console\Command;

class RestoreDepartmentProgrammeLinksCommand extends Command
{
    protected $signature = 'maintenance:restore-department-programme-links
        {--execute : Apply the repair; without this flag the command only reports}';

    protected $description = 'Re-point applications and enrolments at the original department level/course rows that Link Levels/Courses replaced';

    public function handle(DepartmentProgrammeLinkRepairService $service): int
    {
        $plan = $service->plan();

        if ($plan === []) {
            $this->info('No orphaned department level or course links found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Kind', 'Department', 'Catalog id', 'Keep id', 'Restore', 'Retire ids', 'Applications'],
            array_map(fn (array $entry): array => [
                $entry['kind'],
                $entry['department_id'],
                $entry['catalog_id'],
                $entry['canonical_id'],
                $entry['canonical_was_trashed'] ? 'yes' : 'no',
                $entry['duplicates'] === [] ? '-' : implode(', ', $entry['duplicates']),
                $entry['applications'],
            ], $plan),
        );

        if (! $this->option('execute')) {
            $this->warn(sprintf('Dry run: %d link group(s) would be repaired. Re-run with --execute to apply.', count($plan)));

            return self::SUCCESS;
        }

        $summary = $service->execute($plan);

        $this->info(sprintf(
            'Restored %d link(s), retired %d duplicate(s).',
            $summary['restored'],
            $summary['trashed'],
        ));

        foreach ($summary['remapped'] as $table => $count) {
            $this->line(sprintf('  remapped %d row(s) in %s', $count, $table));
        }

        foreach ($summary['merged'] as $table => $count) {
            $this->line(sprintf('  merged %d duplicate row(s) in %s', $count, $table));
        }

        return self::SUCCESS;
    }
}
