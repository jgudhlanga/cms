<?php

declare(strict_types=1);

namespace App\Queries\Applications;

use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Students\StudentApplication;
use Illuminate\Database\Eloquent\Builder;

class ApplicationExportQuery
{
    public function baseQuery(?string $intakeYear = null): Builder
    {
        $rankedPrograms = $this->matchingProgramsQuery($intakeYear)
            ->select([
                'student_applications.id',
                'student_applications.student_id',
            ])
            ->selectRaw($this->exportRankSelectSql(), [
                WorkflowStepEnum::ENROLLED->slug(),
            ]);

        return StudentApplication::query()
            ->whereIn('student_applications.id', function ($query) use ($rankedPrograms): void {
                $query->fromSub($rankedPrograms, 'ranked_programs')
                    ->where('ranked_programs.export_rank', 1)
                    ->select('ranked_programs.id');
            })
            ->orderBy('student_applications.student_id')
            ->with([
                'student.user',
                'student.gender',
                'student.country',
                'student.addresses',
                'student.contacts',
                'departmentLevel',
                'departmentCourse',
                'intakePeriod',
                'modeOfStudy',
                'departmentWorkflowStep.workflowStep',
            ]);
    }

    public function count(?string $intakeYear = null): int
    {
        return $this->baseQuery($intakeYear)->count();
    }

    private function matchingProgramsQuery(?string $intakeYear = null): Builder
    {
        return StudentApplication::query()
            ->leftJoin(
                'department_application_steps',
                'student_applications.department_application_step_id',
                '=',
                'department_application_steps.id',
            )
            ->leftJoin(
                'workflow_steps',
                'department_application_steps.workflow_step_id',
                '=',
                'workflow_steps.id',
            )
            ->whereNull('student_applications.deleted_at')
            ->where(function (Builder $query): void {
                $query->whereNotNull('student_applications.department_application_step_id')
                    ->orWhereExists(function ($subQuery): void {
                        $subQuery->selectRaw('1')
                            ->from('student_enrolments')
                            ->whereColumn('student_enrolments.student_application_id', 'student_applications.id')
                            ->whereNull('student_enrolments.deleted_at');
                    });
            })
            ->when($intakeYear !== null, function (Builder $query) use ($intakeYear): void {
                $query->whereHas('intakePeriod', fn (Builder $intakeQuery) => $intakeQuery
                    ->where('calendar_year', $intakeYear)
                    ->whereNull('intake_periods.deleted_at'));
            });
    }

    private function exportRankSelectSql(): string
    {
        return 'ROW_NUMBER() OVER (
            PARTITION BY student_applications.student_id
            ORDER BY
                CASE
                    WHEN workflow_steps.slug = ? THEN 0
                    WHEN EXISTS (
                        SELECT 1 FROM student_enrolments
                        WHERE student_enrolments.student_application_id = student_applications.id
                        AND student_enrolments.deleted_at IS NULL
                    ) THEN 1
                    ELSE 2
                END,
                student_applications.id
        ) as export_rank';
    }
}
