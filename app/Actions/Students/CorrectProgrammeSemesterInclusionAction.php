<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\StudentEnrolment;
use App\Services\Institution\ProgrammeSemesterResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CorrectProgrammeSemesterInclusionAction
{
    public function __construct(
        protected ProgrammeSemesterResolver $programmeSemesterResolver,
    ) {}

    /**
     * @param  Collection<int, StudentEnrolment>  $enrolments
     * @return array{corrected: int, run_id: int|null}
     */
    public function execute(Collection $enrolments, int $programmeSemesterId, ?int $triggeredBy = null): array
    {
        $programmeSemester = ProgrammeSemester::query()->findOrFail($programmeSemesterId);
        $corrected = 0;
        $runId = null;

        DB::transaction(function () use ($enrolments, $programmeSemester, $triggeredBy, &$corrected, &$runId): void {
            foreach ($enrolments as $enrolment) {
                $currentSemester = $enrolment->currentStudentSemester();

                if ($currentSemester === null) {
                    continue;
                }

                $previousProgrammeSemesterId = $currentSemester->programme_semester_id;
                $dlc = $this->programmeSemesterResolver->resolveDepartmentLevelCourse($enrolment);
                $globalSemester = $dlc !== null
                    ? $this->programmeSemesterResolver->globalSemesterForProgrammeSemester($dlc, $programmeSemester)
                    : null;

                $currentSemester->update([
                    'programme_semester_id' => $programmeSemester->id,
                    'semester_id' => $globalSemester?->id ?? $currentSemester->semester_id,
                ]);

                if ($runId === null) {
                    $runId = (int) DB::table('programme_semester_progression_runs')->insertGetId([
                        'tenant_id' => $enrolment->tenant_id,
                        'triggered_by' => $triggeredBy,
                        'action' => 'correct_inclusion',
                        'affected_count' => 0,
                        'dry_run' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('programme_semester_progression_run_items')->insert([
                    'programme_semester_progression_run_id' => $runId,
                    'student_enrolment_id' => $enrolment->id,
                    'previous_student_semester_id' => $currentSemester->id,
                    'new_student_semester_id' => $currentSemester->id,
                    'previous_programme_semester_id' => $previousProgrammeSemesterId,
                    'new_programme_semester_id' => $programmeSemester->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $corrected++;
            }

            if ($runId !== null) {
                DB::table('programme_semester_progression_runs')
                    ->where('id', $runId)
                    ->update(['affected_count' => $corrected, 'updated_at' => now()]);
            }
        });

        return [
            'corrected' => $corrected,
            'run_id' => $runId,
        ];
    }
}
