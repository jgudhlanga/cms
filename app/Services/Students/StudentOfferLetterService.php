<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\StudentApplication;
use Carbon\CarbonInterface;
use Spatie\Activitylog\Models\Activity;

class StudentOfferLetterService
{
    public function __construct(
        private readonly IntakePeriodResolver $intakePeriodResolver,
    ) {}

    public function issuedAt(StudentApplication $application): ?CarbonInterface
    {
        $application->loadMissing(['classList']);

        $acceptedStepId = $this->acceptedDepartmentStepId($application);
        if ($acceptedStepId !== null) {
            $activity = Activity::query()
                ->where('subject_type', $application->getMorphClass())
                ->where('subject_id', $application->id)
                ->orderBy('created_at')
                ->get()
                ->first(function (Activity $activity) use ($acceptedStepId): bool {
                    $attributes = $activity->properties['attributes'] ?? null;

                    return is_array($attributes)
                        && (int) ($attributes['department_application_step_id'] ?? 0) === $acceptedStepId;
                });

            if ($activity instanceof Activity && $activity->created_at !== null) {
                return $activity->created_at;
            }
        }

        if ($application->classList?->updated_at !== null) {
            return $application->classList->updated_at;
        }

        return $application->updated_at;
    }

    public function isDownloadable(StudentApplication $application): bool
    {
        $application->loadMissing(['classList', 'departmentWorkflowStep.workflowStep']);

        $classListType = $application->classList?->type?->value ?? $application->classList?->type;
        $status = strtolower((string) $application->departmentWorkflowStep?->workflowStep?->name);

        return in_array($classListType, [
            ClassListTypeEnum::VERIFIED->value,
            ClassListTypeEnum::FINAL->value,
        ], true) && in_array($status, [
            strtolower(WorkflowStepEnum::ACCEPTED->name()),
            strtolower(WorkflowStepEnum::ENROLLED->name()),
        ], true);
    }

    public function isCurrentIntake(StudentApplication $application): bool
    {
        return $this->intakePeriodResolver->isCurrentOfferIntake($application);
    }

    private function acceptedDepartmentStepId(StudentApplication $application): ?int
    {
        $workflowStepId = WorkflowStep::query()
            ->where('slug', WorkflowStepEnum::ACCEPTED->slug())
            ->value('id');

        if ($workflowStepId === null) {
            return null;
        }

        $stepId = DepartmentApplicationStep::query()
            ->where('institution_department_id', $application->institution_department_id)
            ->where('workflow_step_id', $workflowStepId)
            ->value('id');

        return $stepId !== null ? (int) $stepId : null;
    }
}
