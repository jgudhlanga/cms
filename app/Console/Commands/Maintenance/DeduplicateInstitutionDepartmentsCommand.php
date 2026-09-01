<?php

declare(strict_types=1);

namespace App\Console\Commands\Maintenance;

use App\Actions\Institution\DeduplicateInstitutionDepartmentsAction;
use Illuminate\Console\Command;

class DeduplicateInstitutionDepartmentsCommand extends Command
{
    protected $signature = 'maintenance:deduplicate-institution-departments
        {--execute : Apply the deduplication; without this flag the command only reports}';

    protected $description = 'Merge duplicate institution_departments rows onto the oldest link per tenant and catalog department';

    public function handle(DeduplicateInstitutionDepartmentsAction $action): int
    {
        $plan = $action->plan();

        if ($plan === []) {
            $this->info('No duplicate institution department links found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Tenant', 'Catalog dept', 'Keep id', 'Retire ids'],
            array_map(fn (array $entry): array => [
                $entry['tenant_id'],
                $entry['department_id'],
                $entry['keeper_id'],
                implode(', ', $entry['duplicate_ids']),
            ], $plan),
        );

        if (! $this->option('execute')) {
            $this->warn(sprintf(
                'Dry run: %d duplicate group(s) would be merged. Re-run with --execute to apply.',
                count($plan),
            ));

            return self::SUCCESS;
        }

        $summary = $action->execute($plan);

        $this->info(sprintf('Retired %d duplicate institution department link(s).', $summary['retired']));

        foreach ($summary['remapped'] as $table => $count) {
            $this->line(sprintf('  remapped %d row(s) in %s', $count, $table));
        }

        foreach ($summary['merged'] as $table => $count) {
            $this->line(sprintf('  merged %d duplicate row(s) in %s', $count, $table));
        }

        return self::SUCCESS;
    }
}
