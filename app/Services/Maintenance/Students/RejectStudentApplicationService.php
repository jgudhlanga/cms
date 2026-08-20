<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Students;

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\StudentApplication;
use Illuminate\Validation\ValidationException;

class RejectStudentApplicationService
{
    public function reject(StudentApplication $studentApplication): StudentApplication
    {
        $workflowSlug = $studentApplication->workflowStep?->slug;

        if (in_array($workflowSlug, [
            WorkflowStepEnum::REJECTED->slug(),
            WorkflowStepEnum::ENROLLED->slug(),
        ], true)) {
            throw ValidationException::withMessages([
                'student_application' => [__('trans.maintenance_faulty_data_merge_reject_not_allowed')],
            ]);
        }

        $rejectedStep = $this->resolveRejectedStep();

        $studentApplication->update([
            'workflow_step_id' => $rejectedStep->id,
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
            'workflowStep',
            'classList',
        ]);
    }

    private function resolveRejectedStep(): WorkflowStep
    {
        return WorkflowStep::query()->firstOrCreate(
            ['slug' => WorkflowStepEnum::REJECTED->slug()],
            [
                'name' => WorkflowStepEnum::REJECTED->name(),
                'description' => WorkflowStepEnum::REJECTED->description(),
                'position' => WorkflowStepEnum::REJECTED->position(),
            ],
        );
    }
}
