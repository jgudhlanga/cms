<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Students;

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\StudentApplication;
use Illuminate\Validation\ValidationException;

class RejectStudentApplicationService
{
    public function reject(StudentApplication $studentApplication): StudentApplication
    {
        $workflowSlug = $studentApplication->departmentWorkflowStep?->workflowStep?->slug;

        if (in_array($workflowSlug, [
            WorkflowStepEnum::REJECTED->slug(),
            WorkflowStepEnum::ENROLLED->slug(),
        ], true)) {
            throw ValidationException::withMessages([
                'student_application' => [__('trans.maintenance_faulty_data_merge_reject_not_allowed')],
            ]);
        }

        $departmentStep = $this->resolveRejectedDepartmentStep($studentApplication);

        $studentApplication->update([
            'department_application_step_id' => $departmentStep->id,
        ]);

        if ($studentApplication->classList !== null) {
            $studentApplication->classList()->update([
                'type' => ClassListTypeEnum::FAILED->value,
            ]);
        }

        return $studentApplication->fresh([
            'institutionDepartment.department',
            'departmentLevel.level',
            'departmentCourse.course',
            'intakePeriod',
            'modeOfStudy',
            'departmentWorkflowStep.workflowStep',
            'classList',
        ]);
    }

    private function resolveRejectedDepartmentStep(StudentApplication $studentApplication): DepartmentApplicationStep
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
                'tenant_id' => $studentApplication->tenant_id,
                'institution_department_id' => $studentApplication->institution_department_id,
                'workflow_step_id' => $rejectedStep->id,
            ],
            [
                'position' => $rejectedStep->position,
            ],
        );
    }
}
