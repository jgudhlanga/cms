<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Actions\Enrolments\SyncStudentApplicationClassListLifecycleAction;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use App\Services\Students\IntakePeriodResolver;
use App\Support\Students\StudentApplicationStatusMapper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateStudentProfileStatusAction
{
    public function __construct(
        private readonly StudentApplicationStatusMapper $mapper,
        private readonly IntakePeriodResolver $intakePeriods,
        private readonly SyncStudentApplicationClassListLifecycleAction $lifecycle,
    ) {}

    public function execute(Student $student, string $statusSlug, string $reason, ?User $actor = null): void
    {
        $workflowStep = $this->mapper->workflowStepBySlug($statusSlug);

        if ($workflowStep === null) {
            throw ValidationException::withMessages([
                'status' => __('students.change_status_invalid'),
            ]);
        }

        $applications = $this->currentIntakeApplications($student);

        if ($applications->isEmpty()) {
            throw ValidationException::withMessages([
                'status' => __('students.change_status_no_current_intake_applications'),
            ]);
        }

        if ($this->mapper->requiresLevel($statusSlug)) {
            $missingLevel = $applications->first(
                fn (StudentApplication $application): bool => $this->mapper->isApplicationMissingLevel($application)
            );

            if ($missingLevel instanceof StudentApplication) {
                throw ValidationException::withMessages([
                    'status' => __('students.change_status_requires_level'),
                ]);
            }
        }

        $previousStatuses = $applications
            ->mapWithKeys(fn (StudentApplication $application): array => [
                (int) $application->id => $application->workflowStep?->name,
            ])
            ->all();

        $classListType = $this->mapper->classListTypeForSlug($statusSlug);
        $stepEnum = $this->mapper->stepEnumBySlug($statusSlug);

        DB::transaction(function () use ($applications, $classListType, $stepEnum): void {
            foreach ($applications as $application) {
                if ($classListType instanceof ClassListTypeEnum) {
                    $this->lifecycle->syncApplicationToType($application, $classListType, false);

                    continue;
                }

                if ($stepEnum instanceof WorkflowStepEnum) {
                    $this->lifecycle->applyWorkflow($application, $stepEnum);
                }
            }
        });

        $this->audit($student, $actor, $workflowStep->name, $previousStatuses, $reason);
    }

    /**
     * @return Collection<int, StudentApplication>
     */
    private function currentIntakeApplications(Student $student): Collection
    {
        $activeIntakePeriodIds = $this->intakePeriods->activeIntakePeriodIds();

        if ($activeIntakePeriodIds === []) {
            return new Collection;
        }

        return $student->applications()
            ->whereIn('intake_period_id', $activeIntakePeriodIds)
            ->with(['workflowStep', 'classList', 'departmentLevel.level', 'student.user', 'institutionDepartment', 'intakePeriod'])
            ->get();
    }

    /**
     * @param  array<int, string|null>  $previousStatuses
     */
    private function audit(
        Student $student,
        ?User $actor,
        string $newStatus,
        array $previousStatuses,
        string $reason,
    ): void {
        $logger = activity('Student')
            ->performedOn($student)
            ->event('status-changed')
            ->withProperties([
                'old_status' => array_values(array_unique(array_filter($previousStatuses))),
                'new_status' => $newStatus,
                'student_application_ids' => array_keys($previousStatuses),
                'reason' => $reason,
            ]);

        if ($actor !== null) {
            $logger->causedBy($actor);
        }

        $logger->log(__('students.change_status_activity_description', ['status' => $newStatus]));
    }
}
