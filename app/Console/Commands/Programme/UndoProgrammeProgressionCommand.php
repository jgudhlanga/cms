<?php

declare(strict_types=1);

namespace App\Console\Commands\Programme;

use App\Models\Students\StudentSemester;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UndoProgrammeProgressionCommand extends Command
{
    protected $signature = 'programme:undo-progression {run : Programme semester progression run id}';

    protected $description = 'Undo a continue-and-reseat or inclusion correction run';

    public function handle(): int
    {
        $runId = (int) $this->argument('run');
        $items = DB::table('programme_semester_progression_run_items')
            ->where('programme_semester_progression_run_id', $runId)
            ->get();

        if ($items->isEmpty()) {
            $this->warn('No run items found.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($items): void {
            foreach ($items as $item) {
                if ($item->previous_student_semester_id !== null) {
                    StudentSemester::query()
                        ->whereKey($item->previous_student_semester_id)
                        ->update([
                            'programme_semester_id' => $item->previous_programme_semester_id,
                        ]);
                }

                if ($item->new_pivot_id !== null) {
                    DB::table('academic_calendar_student_enrolments')
                        ->where('id', $item->new_pivot_id)
                        ->update(['deleted_at' => now(), 'is_live' => false]);
                }

                if ($item->previous_pivot_id !== null) {
                    DB::table('academic_calendar_student_enrolments')
                        ->where('id', $item->previous_pivot_id)
                        ->update([
                            'is_live' => true,
                            'concluded_at' => null,
                            'deleted_at' => null,
                        ]);
                }

                if ($item->new_student_semester_id !== null) {
                    StudentSemester::query()->whereKey($item->new_student_semester_id)->delete();
                }
            }

            DB::table('programme_semester_progression_runs')->where('id', $items->first()->programme_semester_progression_run_id)->delete();
            DB::table('programme_semester_progression_run_items')
                ->where('programme_semester_progression_run_id', $items->first()->programme_semester_progression_run_id)
                ->delete();
        });

        $this->info('Progression run undone.');

        return self::SUCCESS;
    }
}
