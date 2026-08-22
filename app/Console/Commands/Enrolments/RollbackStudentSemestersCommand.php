<?php

declare(strict_types=1);

namespace App\Console\Commands\Enrolments;

use App\Services\Students\RollbackStudentSemestersService;
use Illuminate\Console\Command;

class RollbackStudentSemestersCommand extends Command
{
    protected $signature = 'enrolments:rollback-student-semesters {--dry-run : Report counts without writing}';

    protected $description = 'Restore pre-backfill enrolment data and remove student_semesters rows.';

    public function handle(RollbackStudentSemestersService $rollback): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $result = $rollback->run($dryRun);

        $this->info($dryRun
            ? "Dry run: would restore {$result['pivots']} pivot(s), {$result['enrolments']} enrolment(s), restore {$result['restored']} collapsed enrolment(s), delete {$result['deleted']} student_semester row(s)."
            : "Restored {$result['pivots']} pivot(s), {$result['enrolments']} enrolment(s), restored {$result['restored']} collapsed enrolment(s), deleted student_semesters (remaining: {$result['deleted']}).");

        return self::SUCCESS;
    }
}
