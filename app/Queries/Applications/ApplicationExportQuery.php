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
        return StudentProgram::query()
            ->join(
                'department_application_steps',
                'student_programs.department_application_step_id',
                '=',
                'department_application_steps.id',
            )
            ->join(
                'workflow_steps',
                'department_application_steps.workflow_step_id',
                '=',
                'workflow_steps.id',
            )
            ->whereNull('student_programs.deleted_at')
            ->whereIn('workflow_steps.slug', [
                WorkflowStepEnum::ACCEPTED->slug(),
                WorkflowStepEnum::ENROLLED->slug(),
            ])
            ->when($intakeYear !== null, function (Builder $query) use ($intakeYear): void {
                $query->whereHas('intakePeriod', fn (Builder $intakeQuery) => $intakeQuery
                    ->where('calendar_year', $intakeYear)
                    ->whereNull('intake_periods.deleted_at'));
            })
            ->select('student_programs.*')
            ->orderBy('student_programs.student_id')
            ->orderByRaw("CASE WHEN workflow_steps.slug = '".WorkflowStepEnum::ENROLLED->slug()."' THEN 0 ELSE 1 END")
            ->orderBy('student_programs.id')
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
}
