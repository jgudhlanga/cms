<?php

declare(strict_types=1);

namespace App\Console\Commands\Enrolments;

use App\Actions\Students\CompleteLevelEnrolmentAction;
use App\Exceptions\Students\StudentEnrolmentProgressionException;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
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
    protected $description = 'Mark Active last-phase student_semesters as Award for the whole level.';

    public function handle(
        CompleteLevelEnrolmentAction $completeLevelEnrolment,
        StudentEnrolmentProgressionService $progression,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $targets = $this->matchingSemesters();
        $completed = 0;
        $skipped = 0;

        foreach ($targets as $studentSemester) {
            $enrolment = $studentSemester->enrolment;

            if (! $enrolment instanceof StudentEnrolment || ! $progression->canCompleteLevelSemester($studentSemester)) {
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line("Would complete level for student_semester {$studentSemester->id} (enrolment {$enrolment->id})");
                $completed++;

                continue;
            }

            try {
                $completeLevelEnrolment->execute($studentSemester);
                $completed++;
            } catch (StudentEnrolmentProgressionException $exception) {
                $skipped++;
                $this->warn("Skipped student_semester {$studentSemester->id}: {$exception->getMessage()}");
            }
        }

        $this->info($dryRun
            ? "Dry run: {$completed} student_semester(s) would complete, {$skipped} skipped."
            : "Completed {$completed} student_semester(s), {$skipped} skipped.");

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, StudentSemester>
     */
    private function matchingSemesters()
    {
        return StudentSemester::query()
            ->with(['enrolment.studentApplication', 'studentEnrolmentStatus', 'enrolment.departmentLevel.level', 'enrolment.academicCalendar', 'semester'])
            ->whereNull('deleted_at')
            ->whereHas('enrolment', function (Builder $query): void {
                $query->whereNull('deleted_at')
                    ->when($this->intOption('department'), fn (Builder $inner, int $departmentId) => $inner->where('institution_department_id', $departmentId))
                    ->when($this->intOption('academic-calendar-id'), fn (Builder $inner, int $calendarId) => $inner->where('academic_calendar_id', $calendarId))
                    ->when($this->intOption('mode-of-study-id'), fn (Builder $inner, int $modeId) => $inner->where('mode_of_study_id', $modeId));
            })
            ->when($this->intOption('semester-id'), function (Builder $query, int $semesterId): void {
                $query->where('semester_id', $semesterId);
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
