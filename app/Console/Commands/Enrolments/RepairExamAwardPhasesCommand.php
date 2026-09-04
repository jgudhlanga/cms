<?php

declare(strict_types=1);

namespace App\Console\Commands\Enrolments;

use App\Services\Maintenance\Students\ExamAwardPhaseRepairService;
use Illuminate\Console\Command;

class RepairExamAwardPhasesCommand extends Command
{
    protected $signature = 'enrolments:repair-award-phases {--dry-run : Report the corrections without writing}';

    protected $description = 'Re-file HEXCO awards that were recorded against the level the student moved on to instead of the one the statement covers.';

    public function handle(ExamAwardPhaseRepairService $repair): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $result = $repair->run($dryRun);

        if ($result['repaired'] === [] && $result['skipped'] === []) {
            $this->info('No mis-filed awards found.');

            return self::SUCCESS;
        }

        if ($result['repaired'] !== []) {
            $this->table(
                ['Student', 'Enrolment', 'Session', 'Award level', 'Now on', 'Award phases', 'Phantom phase'],
                array_map(static fn (array $row): array => [
                    $row['student_number'] !== '' ? $row['student_number'] : $row['student_id'],
                    $row['student_enrolment_id'],
                    $row['session'],
                    $row['to_level_name'],
                    $row['current_level_name'],
                    implode(', ', $row['award_phase_ids']) ?: '—',
                    $row['phantom_phase_id'] ?? '—',
                ], $result['repaired']),
            );
        }

        foreach ($result['skipped'] as $row) {
            $this->warn(sprintf(
                'Skipped student %s (enrolment %d): %s',
                $row['student_number'] !== '' ? $row['student_number'] : $row['student_id'],
                $row['student_enrolment_id'],
                $row['skip_reason'],
            ));
        }

        $this->info($dryRun
            ? sprintf('Dry run: would repair %d student(s), skip %d.', count($result['repaired']), count($result['skipped']))
            : sprintf('Repaired %d student(s), skipped %d. Run id: %s (undo with programme:undo-progression).',
                count($result['repaired']),
                count($result['skipped']),
                $result['run_id'] ?? '—',
            ));

        return self::SUCCESS;
    }
}
