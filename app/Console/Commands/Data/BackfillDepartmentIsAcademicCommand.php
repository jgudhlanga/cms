<?php

namespace App\Console\Commands\Data;

use App\Enums\Institution\DepartmentEnum;
use App\Models\Institution\Department;
use Illuminate\Console\Command;

class BackfillDepartmentIsAcademicCommand extends Command
{
    protected $signature = 'departments:backfill-is-academic {--dry-run : Report changes without saving}';

    protected $description = 'Set departments.is_academic from DepartmentEnum by department name';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;
        $skipped = 0;

        foreach (DepartmentEnum::cases() as $departmentCase) {
            $department = Department::query()
                ->where('name', $departmentCase->value)
                ->first();

            if (! $department) {
                $this->warn("Department not found: {$departmentCase->value}");
                $skipped++;

                continue;
            }

            $expected = $departmentCase->isAcademic();

            if ((bool) $department->is_academic === $expected) {
                $skipped++;

                continue;
            }

            $this->line(sprintf(
                '%s: is_academic %s -> %s',
                $department->name,
                $department->is_academic ? 'true' : 'false',
                $expected ? 'true' : 'false',
            ));

            if (! $dryRun) {
                $department->update(['is_academic' => $expected]);
            }

            $updated++;
        }

        $this->info(sprintf(
            '%s %d department(s), skipped %d unchanged or missing.',
            $dryRun ? 'Would update' : 'Updated',
            $updated,
            $skipped,
        ));

        return self::SUCCESS;
    }
}
