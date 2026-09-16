<?php

declare(strict_types=1);

namespace App\Console\Commands\Students;

use App\Enums\Students\StudyPositionAnswerEnum;
use App\Enums\Students\StudyPositionStateEnum;
use App\Enums\Students\StudyPositionSyncStatusEnum;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentStudyPositionConfirmation;
use App\Services\Students\StudyPosition\StudyPositionAutoConfirmer;
use App\Services\Students\StudyPosition\StudyPositionScope;
use App\Services\Students\StudyPosition\StudyPositionService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Nightly: confirms the study positions the records already prove (live class seat, exam Proceed,
 * new intake) so those students are never asked, and clears review flags the records now satisfy.
 */
class AutoConfirmStudyPositionCommand extends Command
{
    protected $signature = 'students:auto-confirm-study-position
        {--dry-run : Report what would happen without saving anything}
        {--chunk=200 : Enrolments per batch}
        {--student= : Only this student id}
        {--department= : Only this institution department id}';

    protected $description = 'Confirm study positions the records already prove, and settle review flags the records now satisfy';

    public function handle(StudyPositionScope $scope, StudyPositionAutoConfirmer $autoConfirmer): int
    {
        if ($scope->currentPeriodIds() === []) {
            $this->warn('No calendar period has opened yet; there is nothing to confirm.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));

        if ($dryRun) {
            $this->info('Dry run: every change is rolled back.');
        }

        $this->line('Current period: '.$scope->periodLabel());

        $outcomes = array_fill_keys([
            StudyPositionAutoConfirmer::APPLIED,
            StudyPositionAutoConfirmer::KEPT,
            StudyPositionAutoConfirmer::CONFLICT,
            StudyPositionAutoConfirmer::NO_EVIDENCE,
            StudyPositionAutoConfirmer::REFUSED,
        ], 0);

        $query = $scope->whereState(
            $scope->constrain(StudentEnrolment::query()),
            StudyPositionStateEnum::UNCONFIRMED,
        );

        $this->applyFilters($query)
            ->with(['departmentLevel.level', 'modeOfStudy', 'studentSemesters.semester', 'studentSemesters.programmeSemester'])
            ->chunkById($chunk, function (Collection $enrolments) use ($autoConfirmer, $dryRun, &$outcomes): void {
                foreach ($enrolments as $enrolment) {
                    $outcomes[$this->runOne($autoConfirmer, $enrolment, $dryRun)]++;
                }
            }, 'student_enrolments.id', 'id');

        $resolved = $this->settleResolvedReviews($scope, $chunk, $dryRun);

        $this->table(['Outcome', 'Enrolments'], [
            ['Records changed to the detected phase', $outcomes[StudyPositionAutoConfirmer::APPLIED]],
            ['Confirmed as already recorded', $outcomes[StudyPositionAutoConfirmer::KEPT]],
            ['Rules disagreed (student will be asked)', $outcomes[StudyPositionAutoConfirmer::CONFLICT]],
            ['No evidence (student will be asked)', $outcomes[StudyPositionAutoConfirmer::NO_EVIDENCE]],
            ['Write refused (student will be asked)', $outcomes[StudyPositionAutoConfirmer::REFUSED]],
            ['Review flags settled', $resolved],
        ]);

        return self::SUCCESS;
    }

    private function runOne(StudyPositionAutoConfirmer $autoConfirmer, StudentEnrolment $enrolment, bool $dryRun): string
    {
        if ($dryRun) {
            DB::beginTransaction();
        }

        try {
            return $autoConfirmer->run($enrolment);
        } catch (Throwable $exception) {
            report($exception);

            return StudyPositionAutoConfirmer::REFUSED;
        } finally {
            if ($dryRun) {
                DB::rollBack();
            }
        }
    }

    /**
     * A review flag exists because the records could not take the answer. Once registry has fixed
     * the records so they hold the answered phase in this period's slot, the flag has done its job.
     */
    private function settleResolvedReviews(StudyPositionScope $scope, int $chunk, bool $dryRun): int
    {
        $resolved = 0;

        StudentStudyPositionConfirmation::query()
            ->withoutGlobalScopes()
            ->forPeriods($scope->currentPeriodIds())
            ->where('answer', StudyPositionAnswerEnum::PHASE->value)
            ->where('sync_status', StudyPositionSyncStatusEnum::NEEDS_REVIEW->value)
            ->when($this->option('student'), fn ($query, $studentId) => $query->where('student_id', (int) $studentId))
            ->with(['enrolment.studentSemesters.semester', 'enrolment.studentSemesters.programmeSemester'])
            ->chunkById($chunk, function (Collection $confirmations) use ($dryRun, &$resolved): void {
                foreach ($confirmations as $confirmation) {
                    $current = $confirmation->enrolment?->currentStudentSemester();

                    if ($current === null
                        || (int) $current->programme_semester_id !== (int) $confirmation->programme_semester_id
                        || (int) $current->semester_id !== (int) $confirmation->semester_id) {
                        continue;
                    }

                    $resolved++;

                    if ($dryRun) {
                        continue;
                    }

                    $confirmation->update([
                        'sync_status' => StudyPositionSyncStatusEnum::UNCHANGED,
                        'sync_note' => null,
                        'student_semester_id' => $current->id,
                    ]);

                    StudyPositionService::forget((int) $confirmation->student_id);
                }
            });

        return $resolved;
    }

    /**
     * @template TBuilder of \Illuminate\Database\Eloquent\Builder
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    private function applyFilters($query)
    {
        return $query
            ->when($this->option('student'), fn ($q, $studentId) => $q->where('student_enrolments.student_id', (int) $studentId))
            ->when($this->option('department'), fn ($q, $departmentId) => $q->where(
                'student_enrolments.institution_department_id',
                (int) $departmentId,
            ));
    }
}
