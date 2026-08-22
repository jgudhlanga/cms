<?php

declare(strict_types=1);

namespace App\Console\Commands\Enrolments;

use App\Actions\Students\AdvanceToNextSemesterAction;
use App\Exceptions\Students\StudentEnrolmentProgressionException;
use App\Models\Students\StudentEnrolment;
use App\Services\Students\StudentEnrolmentProgressionService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AdvanceEnrolmentPhaseCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'enrolments:advance-phase
                            {--department= : Institution department id}
                            {--academic-calendar-id= : Academic calendar id}
                            {--semester-id= : Current programme semester id}
                            {--mode-of-study-id= : Mode of study id}
                            {--dry-run : List matching enrolments without creating the next phase}';

    /**
     * @var string
     */
    protected $description = 'Create the next-phase student_semester row for Active students who are not on the last phase.';

    public function handle(
        AdvanceToNextSemesterAction $advanceToNextSemester,
        StudentEnrolmentProgressionService $progression,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $enrolments = $this->matchingEnrolments();
        $advanced = 0;
        $skipped = 0;

        foreach ($enrolments as $enrolment) {
            if (! $progression->canAdvanceToNextPhase($enrolment)) {
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line("Would advance enrolment {$enrolment->id} (student {$enrolment->student_id})");
                $advanced++;

                continue;
            }

            try {
                $advanceToNextSemester->execute($enrolment);
                $advanced++;
            } catch (StudentEnrolmentProgressionException $exception) {
                $skipped++;
                $this->warn("Skipped enrolment {$enrolment->id}: {$exception->getMessage()}");
            }
        }

        $this->info($dryRun
            ? "Dry run: {$advanced} enrolment(s) would advance, {$skipped} skipped."
            : "Advanced {$advanced} enrolment(s), {$skipped} skipped.");

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, StudentEnrolment>
     */
    private function matchingEnrolments()
    {
        return StudentEnrolment::query()
            ->with(['studentApplication', 'studentEnrolmentStatus', 'departmentLevel.level', 'academicCalendar', 'studentSemesters.semester'])
            ->whereNull('deleted_at')
            ->when($this->intOption('department'), function (Builder $query, int $departmentId): void {
                $query->where('institution_department_id', $departmentId);
            })
            ->when($this->intOption('academic-calendar-id'), function (Builder $query, int $calendarId): void {
                $query->where('academic_calendar_id', $calendarId);
            })
            ->when($this->intOption('semester-id'), function (Builder $query, int $semesterId): void {
                $query->whereHas('studentSemesters', fn (Builder $inner) => $inner
                    ->where('semester_id', $semesterId)
                    ->whereNull('deleted_at'));
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
