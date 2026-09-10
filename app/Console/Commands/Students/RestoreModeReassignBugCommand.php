<?php

declare(strict_types=1);

namespace App\Console\Commands\Students;

use App\Services\Students\RestoreModeReassignBugService;
use Illuminate\Console\Command;

class RestoreModeReassignBugCommand extends Command
{
    protected $signature = 'students:restore-mode-reassign-bug
        {--csv= : Path to mode_reassign_restore.csv}
        {--course-modes-csv= : Path to course_level_modes_restore.csv}
        {--dry-run : Report what would change without writing}
        {--execute : Apply the restore}';

    protected $description = 'Restore applications/enrolments/exam results/course modes moved by the reassign mode-pill bug';

    public function handle(RestoreModeReassignBugService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $execute = (bool) $this->option('execute');

        if ($dryRun === $execute) {
            $this->error('Specify exactly one of --dry-run or --execute.');

            return self::FAILURE;
        }

        $csv = (string) ($this->option('csv') ?: storage_path('app/mode-restore/mode_reassign_restore.csv'));
        $courseModesCsv = (string) ($this->option('course-modes-csv') ?: storage_path('app/mode-restore/course_level_modes_restore.csv'));

        if (! is_file($csv)) {
            $this->error('Applications CSV not found: '.$csv);

            return self::FAILURE;
        }

        if (! is_file($courseModesCsv)) {
            $this->warn('Course modes CSV not found; continuing without CourseLevelMode restores: '.$courseModesCsv);
            $courseModesCsv = '';
        }

        $summary = $service->run($csv, $courseModesCsv !== '' ? $courseModesCsv : null, $dryRun);

        $this->table(
            ['Incident', 'Applications'],
            collect($summary['incidents'])
                ->map(fn (int $count, string $incident): array => [$incident, $count])
                ->values()
                ->all(),
        );

        $key = $dryRun ? 'would' : 'restored';

        $this->info(sprintf(
            '%s applications=%d enrolments=%d exams=%d course_level_modes=%d (skipped apps=%d enrolments=%d exams=%d modes=%d)',
            $dryRun ? 'Dry run' : 'Executed',
            $summary['applications'][$key],
            $summary['enrolments'][$key],
            $summary['exams'][$key],
            $summary['course_level_modes'][$key],
            $summary['applications']['skipped'],
            $summary['enrolments']['skipped'],
            $summary['exams']['skipped'],
            $summary['course_level_modes']['skipped'],
        ));

        return self::SUCCESS;
    }
}
