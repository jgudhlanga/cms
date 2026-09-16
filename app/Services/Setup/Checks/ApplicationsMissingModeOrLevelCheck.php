<?php

declare(strict_types=1);

namespace App\Services\Setup\Checks;

use App\Enums\Setup\SetupGapCheckEnum;
use App\Support\Setup\SetupGapResult;
use Illuminate\Support\Facades\DB;

/**
 * Applications that cannot be placed at all because they carry no mode of study or no level.
 *
 * FaultyApplicationAnalysis classifies these one application at a time for the maintenance screen; here
 * they are counted per department so the department sees that it has some.
 */
class ApplicationsMissingModeOrLevelCheck extends ProgrammeSetupCheck
{
    public function key(): SetupGapCheckEnum
    {
        return SetupGapCheckEnum::APPLICATIONS_MISSING_MODE_OR_LEVEL;
    }

    /**
     * @return iterable<SetupGapResult>
     */
    public function run(): iterable
    {
        $intakePeriodId = $this->currentIntakePeriodId();

        if ($intakePeriodId === null) {
            return [];
        }

        $rows = DB::table('student_applications as sa')
            ->where('sa.intake_period_id', $intakePeriodId)
            ->whereNull('sa.deleted_at')
            ->whereNotNull('sa.institution_department_id')
            ->where(function ($query): void {
                $query->whereNull('sa.mode_of_study_id')->orWhereNull('sa.department_level_id');
            })
            ->groupBy('sa.institution_department_id')
            ->select([
                'sa.institution_department_id',
                DB::raw('COUNT(*) as application_count'),
                DB::raw('SUM(CASE WHEN sa.mode_of_study_id IS NULL THEN 1 ELSE 0 END) as missing_mode_count'),
                DB::raw('SUM(CASE WHEN sa.department_level_id IS NULL THEN 1 ELSE 0 END) as missing_level_count'),
            ])
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $departments = $this->departments($rows->pluck('institution_department_id')->map(fn ($id): int => (int) $id)->unique()->values()->all());

        $gaps = [];

        foreach ($rows as $row) {
            $departmentId = (int) $row->institution_department_id;
            $department = $departments[$departmentId] ?? null;

            if ($department === null) {
                continue;
            }

            $missingMode = (int) $row->missing_mode_count;
            $missingLevel = (int) $row->missing_level_count;

            $missingLabel = match (true) {
                $missingMode > 0 && $missingLevel > 0 => __('setup_gaps.missing_mode_and_level'),
                $missingMode > 0 => __('setup_gaps.missing_mode_of_study'),
                default => __('setup_gaps.missing_level'),
            };

            $gaps[] = new SetupGapResult(
                check: $this->key(),
                tenantId: $department['tenantId'],
                title: __('setup_gaps.applications_missing_mode_or_level_title', [
                    'count' => (int) $row->application_count,
                    'missing' => $missingLabel,
                ]),
                body: __('setup_gaps.applications_missing_mode_or_level_body'),
                institutionDepartmentId: $departmentId,
                url: $this->departmentUrl($departmentId),
                meta: [
                    'applicationCount' => (int) $row->application_count,
                    'missingModeCount' => $missingMode,
                    'missingLevelCount' => $missingLevel,
                    'intakePeriodId' => $intakePeriodId,
                ],
                // Intake-scoped: a new intake raises its own gap rather than reopening last intake's.
                fingerprintKey: "intake:{$intakePeriodId}|missing-mode-or-level",
            );
        }

        return $gaps;
    }
}
