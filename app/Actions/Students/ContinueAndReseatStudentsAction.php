<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Exceptions\Students\StudentEnrolmentProgressionException;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
use App\Services\Institution\ProgrammeSemesterResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ContinueAndReseatStudentsAction
{
    public function __construct(
        protected AdvanceToNextSemesterAction $advanceToNextSemester,
        protected ProgrammeSemesterResolver $programmeSemesterResolver,
    ) {}

    /**
     * @param  Collection<int, StudentEnrolment>  $enrolments
     * @return array{advanced: int, reseated: int, run_id: int|null}
     */
    public function execute(
        Collection $enrolments,
        AcademicCalendarClass $sourceClass,
        ?int $triggeredBy = null,
        bool $dryRun = false,
    ): array {
        $advanced = 0;
        $reseated = 0;
        $runId = null;

        $sourceClass->loadMissing('classConfig');

        foreach ($enrolments as $enrolment) {
            $currentPivot = AcademicCalendarStudentEnrolment::query()
                ->where('student_enrolment_id', $enrolment->id)
                ->where('academic_calendar_class_id', $sourceClass->id)
                ->where('is_live', true)
                ->whereNull('deleted_at')
                ->first();

            $previousStudentSemester = $enrolment->currentStudentSemester();
            $previousPivotId = $currentPivot?->id;
            $previousProgrammeSemesterId = $previousStudentSemester?->programme_semester_id;

            if ($dryRun) {
                $advanced++;

                continue;
            }

            try {
                $updatedEnrolment = $this->advanceToNextSemester->execute($enrolment);
            } catch (StudentEnrolmentProgressionException) {
                continue;
            }

            $advanced++;
            $newStudentSemester = $updatedEnrolment->currentStudentSemester();

            if ($currentPivot instanceof AcademicCalendarStudentEnrolment) {
                $currentPivot->update([
                    'is_live' => false,
                    'concluded_at' => now(),
                ]);
            }

            $targetClass = $this->resolveTargetClass($updatedEnrolment, $sourceClass, $newStudentSemester);
            $newPivot = null;

            if ($targetClass instanceof AcademicCalendarClass) {
                $newPivot = AcademicCalendarStudentEnrolment::query()->create([
                    'student_enrolment_id' => $updatedEnrolment->id,
                    'student_semesters_id' => $newStudentSemester?->id,
                    'academic_calendar_class_id' => $targetClass->id,
                    'is_live' => true,
                ]);
                $reseated++;

                if ($runId === null) {
                    $runId = (int) DB::table('programme_semester_progression_runs')->insertGetId([
                        'tenant_id' => $sourceClass->tenant_id,
                        'academic_calendar_class_id' => $sourceClass->id,
                        'triggered_by' => $triggeredBy,
                        'action' => 'continue_and_reseat',
                        'affected_count' => 0,
                        'dry_run' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('programme_semester_progression_run_items')->insert([
                    'programme_semester_progression_run_id' => $runId,
                    'student_enrolment_id' => $updatedEnrolment->id,
                    'previous_student_semester_id' => $previousStudentSemester?->id,
                    'new_student_semester_id' => $newStudentSemester?->id,
                    'previous_pivot_id' => $previousPivotId,
                    'new_pivot_id' => $newPivot->id,
                    'previous_programme_semester_id' => $previousProgrammeSemesterId,
                    'new_programme_semester_id' => $newStudentSemester?->programme_semester_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($newPivot instanceof AcademicCalendarStudentEnrolment) {
                AcademicCalendarStudentEnrolment::query()
                    ->where('student_enrolment_id', $updatedEnrolment->id)
                    ->where('id', '!=', $newPivot->id)
                    ->where('is_live', true)
                    ->update([
                        'is_live' => false,
                        'concluded_at' => now(),
                    ]);
            }
        }

        if ($runId !== null) {
            DB::table('programme_semester_progression_runs')
                ->where('id', $runId)
                ->update(['affected_count' => $advanced, 'updated_at' => now()]);
        }

        return compact('advanced', 'reseated', 'run_id');
    }

    private function resolveTargetClass(
        StudentEnrolment $enrolment,
        AcademicCalendarClass $sourceClass,
        ?StudentSemester $newStudentSemester,
    ): ?AcademicCalendarClass {
        $sourceConfig = $sourceClass->classConfig;

        if (! $sourceConfig instanceof ClassConfig || ! $newStudentSemester instanceof StudentSemester) {
            return null;
        }

        $programmeSemester = $this->programmeSemesterResolver->programmeSemesterForStudentSemester($newStudentSemester);

        if (! $programmeSemester instanceof ProgrammeSemester) {
            return null;
        }

        $targetConfig = ClassConfig::query()
            ->where('calendar_year', $sourceConfig->calendar_year)
            ->where('institution_department_id', $sourceConfig->institution_department_id)
            ->where('department_course_id', $sourceConfig->department_course_id)
            ->where('department_level_id', $sourceConfig->department_level_id)
            ->where('mode_of_study_id', $sourceConfig->mode_of_study_id)
            ->where('programme_semester_id', $programmeSemester->id)
            ->where('slug', 'standard')
            ->first();

        if (! $targetConfig instanceof ClassConfig) {
            return null;
        }

        return AcademicCalendarClass::query()
            ->where('class_config_id', $targetConfig->id)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->first();
    }
}
