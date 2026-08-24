<?php

declare(strict_types=1);

namespace App\Console\Commands\Enrolments;

use App\Services\Students\BackfillStudentSemestersService;
use Illuminate\Console\Command;

class BackfillStudentSemestersCommand extends Command
{
    protected $signature = 'enrolments:backfill-student-semesters {--dry-run : Report counts without writing}';

    protected $description = 'Backfill student_semesters rows and link class pivots (idempotent).';

    public function handle(BackfillStudentSemestersService $backfill): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $result = $backfill->run($dryRun);

        $this->info($dryRun
            ? "Dry run: would collapse {$result['collapsed']} duplicate enrolment(s), sync {$result['synced']} enrolment(s)."
            : "Collapsed {$result['collapsed']} duplicate enrolment(s), synced {$result['synced']} enrolment(s), linked {$result['pivots']} class pivot(s).");

        return self::SUCCESS;
    }
}
