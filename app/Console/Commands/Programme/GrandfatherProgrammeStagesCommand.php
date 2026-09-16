<?php

declare(strict_types=1);

namespace App\Console\Commands\Programme;

use App\Actions\Institution\SyncProgrammeSemestersForOfferingAction;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Institution\ProgrammeStage;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
use App\Services\Students\ProgrammeStageCompletionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GrandfatherProgrammeStagesCommand extends Command
{
    protected $signature = 'programme:grandfather-stages
                            {--dry-run : Report changes without writing}
                            {--limit=0 : Limit number of applications processed}';

    protected $description = 'Attach programme_stage_id to in-flight applications/enrolments and record completed stages without forcing re-admission.';

    public function handle(
        SyncProgrammeSemestersForOfferingAction $sync,
        ProgrammeStageCompletionService $stageCompletion,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));

        $updatedApplications = 0;
        $updatedEnrolments = 0;
        $updatedInclusions = 0;
        $recordedStages = 0;

        DepartmentLevelCourse::query()
            ->with(['departmentLevel.level'])
            ->orderBy('id')
            ->chunkById(50, function ($offerings) use ($sync): void {
                foreach ($offerings as $offering) {
                    $sync->execute($offering);
                }
            });

        $query = StudentApplication::query()
            ->with([
                'programmeStage',
                'enrolments.programmeStage',
                'enrolments.student',
                'enrolments.studentSemesters.semester',
                'enrolments.studentSemesters.programmeSemester.programmeStage',
            ])
            ->whereNull('deleted_at')
            ->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $query->chunkById(100, function ($applications) use (
            $dryRun,
            &$updatedApplications,
            &$updatedEnrolments,
            &$updatedInclusions,
            &$recordedStages,
            $stageCompletion,
        ): void {
            foreach ($applications as $application) {
                DB::transaction(function () use (
                    $application,
                    $dryRun,
                    &$updatedApplications,
                    &$updatedEnrolments,
                    &$updatedInclusions,
                    &$recordedStages,
                    $stageCompletion,
                ): void {
                    $inferredStage = $this->inferStageForApplication($application);

                    if ($inferredStage instanceof ProgrammeStage
                        && (int) $application->programme_stage_id !== (int) $inferredStage->id
                    ) {
                        if (! $dryRun) {
                            $application->update(['programme_stage_id' => $inferredStage->id]);
                        }
                        $updatedApplications++;
                    }

                    foreach ($application->enrolments as $enrolment) {
                        $stage = $this->inferStageForEnrolment($enrolment) ?? $inferredStage;

                        if ($stage instanceof ProgrammeStage
                            && (int) $enrolment->programme_stage_id !== (int) $stage->id
                        ) {
                            if (! $dryRun) {
                                $enrolment->update(['programme_stage_id' => $stage->id]);
                            }
                            $updatedEnrolments++;
                        }

                        foreach ($enrolment->studentSemesters as $studentSemester) {
                            if ($studentSemester->programme_semester_id !== null) {
                                continue;
                            }

                            $programmeSemester = $this->mapInclusionToProgrammeSemester(
                                $enrolment,
                                $studentSemester,
                                $stage,
                            );

                            if (! $programmeSemester instanceof ProgrammeSemester) {
                                continue;
                            }

                            if (! $dryRun) {
                                $studentSemester->update([
                                    'programme_semester_id' => $programmeSemester->id,
                                ]);
                            }
                            $updatedInclusions++;
                        }

                        $student = $enrolment->student;
                        $stageToRecord = $stage ?? $enrolment->programmeStage;

                        if ($student !== null && $stageToRecord instanceof ProgrammeStage) {
                            if ($dryRun) {
                                if ($stageCompletion->isStageComplete($student, $stageToRecord)) {
                                    $recordedStages++;
                                }
                            } else {
                                $record = $stageCompletion->recordIfComplete(
                                    $student,
                                    $stageToRecord,
                                    $application,
                                );
                                if ($record?->completed_at !== null) {
                                    $recordedStages++;
                                }
                            }
                        }
                    }
                });
            }
        });

        $this->info(($dryRun ? '[dry-run] ' : '').'Applications updated: '.$updatedApplications);
        $this->info(($dryRun ? '[dry-run] ' : '').'Enrolments updated: '.$updatedEnrolments);
        $this->info(($dryRun ? '[dry-run] ' : '').'Student semester pins updated: '.$updatedInclusions);
        $this->info(($dryRun ? '[dry-run] ' : '').'Stage completions recorded: '.$recordedStages);

        return self::SUCCESS;
    }

    private function inferStageForApplication(StudentApplication $application): ?ProgrammeStage
    {
        if ($application->programmeStage instanceof ProgrammeStage) {
            return $application->programmeStage;
        }

        $latestEnrolment = $application->enrolments->sortByDesc('id')->first();

        if ($latestEnrolment instanceof StudentEnrolment) {
            return $this->inferStageForEnrolment($latestEnrolment);
        }

        return ProgrammeStage::query()
            ->whereHas('departmentLevelCourse', function ($query) use ($application): void {
                $query
                    ->where('department_course_id', $application->department_course_id)
                    ->where('department_level_id', $application->department_level_id);
            })
            ->orderBy('stage_number')
            ->first();
    }

    private function inferStageForEnrolment(StudentEnrolment $enrolment): ?ProgrammeStage
    {
        if ($enrolment->programmeStage instanceof ProgrammeStage) {
            return $enrolment->programmeStage;
        }

        $current = $enrolment->studentSemesters
            ->sortByDesc('id')
            ->first(fn (StudentSemester $row): bool => $row->programmeSemester?->programmeStage !== null);

        if ($current?->programmeSemester?->programmeStage instanceof ProgrammeStage) {
            return $current->programmeSemester->programmeStage;
        }

        return ProgrammeStage::query()
            ->whereHas('departmentLevelCourse', function ($query) use ($enrolment): void {
                $query
                    ->where('department_course_id', $enrolment->department_course_id)
                    ->where('department_level_id', $enrolment->department_level_id);
            })
            ->orderBy('stage_number')
            ->first();
    }

    private function mapInclusionToProgrammeSemester(
        StudentEnrolment $enrolment,
        StudentSemester $studentSemester,
        ?ProgrammeStage $stage,
    ): ?ProgrammeSemester {
        if (! $stage instanceof ProgrammeStage) {
            return null;
        }

        $stage->loadMissing('programmeSemesters');
        $periodInYear = null;

        if ($studentSemester->semester_id !== null) {
            $slug = (string) ($studentSemester->semester?->slug ?? '');
            if (preg_match('/-(\d+)$/', $slug, $matches) === 1) {
                $periodInYear = (int) $matches[1];
            }
        }

        if ($periodInYear === null) {
            return $stage->programmeSemesters->first();
        }

        return $stage->programmeSemesters
            ->first(fn (ProgrammeSemester $ps): bool => (int) $ps->period_in_year === $periodInYear)
            ?? $stage->programmeSemesters->first();
    }
}
