<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Students;

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\StudentProgram;
use Illuminate\Validation\ValidationException;

class RejectStudentProgramApplicationService
{
    public function reject(StudentProgram $studentProgram): StudentProgram
    {
        $workflowSlug = $studentProgram->departmentWorkflowStep?->workflowStep?->slug;

        if (in_array($workflowSlug, [
            WorkflowStepEnum::REJECTED->slug(),
            WorkflowStepEnum::ENROLLED->slug(),
        ], true)) {
            throw ValidationException::withMessages([
                'student_program' => [__('trans.maintenance_faulty_data_merge_reject_not_allowed')],
            ]);
        }

        $departmentStep = $this->resolveRejectedDepartmentStep($studentProgram);

        $studentProgram->update([
            'department_application_step_id' => $departmentStep->id,
        ]);

        if ($studentProgram->classList !== null) {
            $studentProgram->classList()->update([
                'type' => ClassListTypeEnum::FAILED->value,
            ]);
        }

        return $studentProgram->fresh([
            'institutionDepartment.department',
            'departmentLevel.level',
            'departmentCourse.course',
            'intakePeriod',
            'modeOfStudy',
            'departmentWorkflowStep.workflowStep',
            'classList',
        ]);
    }

    private function resolveRejectedDepartmentStep(StudentProgram $studentProgram): DepartmentApplicationStep
    {
        $rejectedStep = WorkflowStep::query()->firstOrCreate(
            ['slug' => WorkflowStepEnum::REJECTED->slug()],
            [
                'name' => WorkflowStepEnum::REJECTED->name(),
                'description' => WorkflowStepEnum::REJECTED->description(),
                'position' => WorkflowStepEnum::REJECTED->position(),
            ],
        );

        return DepartmentApplicationStep::query()->firstOrCreate(
            [
                'tenant_id' => $studentProgram->tenant_id,
                'institution_department_id' => $studentProgram->institution_department_id,
                'workflow_step_id' => $rejectedStep->id,
            ],
            [
                'position' => $rejectedStep->position,
            ],
        );
    }
}
