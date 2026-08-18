<?php

declare(strict_types=1);

namespace App\Console\Commands\Enrolments;

use App\Actions\Students\CompleteLevelEnrolmentAction;
use App\Exceptions\Students\StudentEnrolmentProgressionException;
use App\Models\Students\StudentEnrolment;
use App\Services\Students\StudentEnrolmentProgressionService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CompleteLevelEnrolmentCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'enrolments:complete-level
                            {--department= : Institution department id}
                            {--academic-calendar-id= : Academic calendar id}
                            {--semester-id= : Programme semester id (must be the last phase)}
                            {--mode-of-study-id= : Mode of study id}
                            {--dry-run : List matching enrolments without marking the level completed}';

    /**
     * @var string
     */
    protected $description = 'Mark Active last-phase enrolments as Completed for the whole level.';

    public function handle(
        CompleteLevelEnrolmentAction $completeLevelEnrolment,
        StudentEnrolmentProgressionService $progression,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $enrolments = $this->matchingEnrolments();
        $completed = 0;
        $skipped = 0;

        foreach ($enrolments as $enrolment) {
            if (! $progression->canCompleteLevel($enrolment)) {
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line("Would complete level for enrolment {$enrolment->id} (student {$enrolment->student_id})");
                $completed++;

                continue;
            }

            try {
                $completeLevelEnrolment->execute($enrolment);
                $completed++;
            } catch (StudentEnrolmentProgressionException $exception) {
                $skipped++;
                $this->warn("Skipped enrolment {$enrolment->id}: {$exception->getMessage()}");
            }
        }

        $this->info($dryRun
            ? "Dry run: {$completed} enrolment(s) would complete, {$skipped} skipped."
            : "Completed {$completed} enrolment(s), {$skipped} skipped.");

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, StudentEnrolment>
     */
    private function matchingEnrolments()
    {
        return StudentEnrolment::query()
            ->with(['studentApplication', 'studentEnrolmentStatus', 'departmentLevel.level', 'academicCalendar'])
            ->whereNull('deleted_at')
            ->when($this->intOption('department'), function (Builder $query, int $departmentId): void {
                $query->where('institution_department_id', $departmentId);
            })
            ->when($this->intOption('academic-calendar-id'), function (Builder $query, int $calendarId): void {
                $query->where('academic_calendar_id', $calendarId);
            })
            ->when($this->intOption('semester-id'), function (Builder $query, int $semesterId): void {
                $query->where('semester_id', $semesterId);
            })
            ->when($this->intOption('mode-of-study-id'), function (Builder $query, int $modeId): void {
                $query->where('mode_of_study_id', $modeId);
            })
            ->orderBy('id')
            ->get();
    }

    private function intOption(string $name): ?int
    {
        $value = $this->option($name);

        if (! is_numeric($value)) {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }
}
