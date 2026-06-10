<?php

declare(strict_types=1);

namespace App\Queries\Applications;

use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Students\StudentProgram;
use Illuminate\Database\Eloquent\Builder;

class ApplicationExportQuery
{
    public function baseQuery(?string $intakeYear = null): Builder
    {
        $rankedPrograms = $this->matchingProgramsQuery($intakeYear)
            ->select([
                'student_programs.id',
                'student_programs.student_id',
            ])
            ->selectRaw($this->exportRankSelectSql(), [
                WorkflowStepEnum::ENROLLED->slug(),
            ]);

        return StudentProgram::query()
            ->whereIn('student_programs.id', function ($query) use ($rankedPrograms): void {
                $query->fromSub($rankedPrograms, 'ranked_programs')
                    ->where('ranked_programs.export_rank', 1)
                    ->select('ranked_programs.id');
            })
            ->orderBy('student_programs.student_id')
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
        return StudentProgram::query()
            ->leftJoin(
                'department_application_steps',
                'student_programs.department_application_step_id',
                '=',
                'department_application_steps.id',
            )
            ->leftJoin(
                'workflow_steps',
                'department_application_steps.workflow_step_id',
                '=',
                'workflow_steps.id',
            )
            ->whereNull('student_programs.deleted_at')
            ->where(function (Builder $query): void {
                $query->whereNotNull('student_programs.department_application_step_id')
                    ->orWhereExists(function ($subQuery): void {
                        $subQuery->selectRaw('1')
                            ->from('student_enrolments')
                            ->whereColumn('student_enrolments.student_program_id', 'student_programs.id')
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
            PARTITION BY student_programs.student_id
            ORDER BY
                CASE
                    WHEN workflow_steps.slug = ? THEN 0
                    WHEN EXISTS (
                        SELECT 1 FROM student_enrolments
                        WHERE student_enrolments.student_program_id = student_programs.id
                        AND student_enrolments.deleted_at IS NULL
                    ) THEN 1
                    ELSE 2
                END,
                student_programs.id
        ) as export_rank';
    }
}
