<?php

declare(strict_types=1);

namespace App\Queries\Maintenance;

use App\Enums\Acl\RoleEnum;
use App\Enums\Maintenance\MaintenanceApplicationStatusFilterEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;

class NonEnrolledStudentUsersQuery
{
    public function baseQuery(int $tenantId): Builder
    {
        return User::query()
            ->role(RoleEnum::STUDENT->name())
            ->where('tenant_id', $tenantId)
            ->where(function (Builder $query): void {
                $query->whereDoesntHave('studentProfile')
                    ->orWhereHas('studentProfile', fn (Builder $profile) => $profile->whereDoesntHave('programs'))
                    ->orWhereHas('studentProfile.programs', fn (Builder $program) => $program
                        ->whereNull('student_programs.deleted_at')
                        ->where(function (Builder $status): void {
                            $status->whereHas(
                                'departmentWorkflowStep.workflowStep',
                                fn (Builder $workflowStep) => $workflowStep->whereIn('slug', [
                                    WorkflowStepEnum::REVIEW->slug(),
                                    WorkflowStepEnum::WAITLISTED->slug(),
                                ]),
                            )->orWhereHas(
                                'classList',
                                fn (Builder $classList) => $classList->where(
                                    'type',
                                    ClassListTypeEnum::VERIFIED->value,
                                ),
                            );
                        }),
                    );
            })
            ->whereDoesntHave(
                'studentProfile.enrolments',
                fn (Builder $enrolment) => $enrolment->whereHas(
                    'studentEnrolmentStatus',
                    fn (Builder $status) => $status->where('slug', 'active'),
                ),
            )
            ->with([
                'status',
                'roles',
                'studentProfile',
                'studentProfile.programs.departmentWorkflowStep.workflowStep',
                'studentProfile.programs.classList',
            ])
            ->orderBy('first_name')
            ->orderBy('last_name');
    }

    public function applySearch(Builder $query, ?string $search): Builder
    {
        if ($search === null || trim($search) === '') {
            return $query;
        }

        $term = '%'.trim($search).'%';

        return $query->where(function (Builder $builder) use ($term): void {
            $builder->where('first_name', 'like', $term)
                ->orWhere('middle_name', 'like', $term)
                ->orWhere('last_name', 'like', $term)
                ->orWhere('email', 'like', $term)
                ->orWhereHas('studentProfile', function (Builder $profile) use ($term): void {
                    $profile->where('student_number', 'like', $term)
                        ->orWhere('id_number', 'like', $term)
                        ->orWhere('passport_number', 'like', $term);
                });
        });
    }

    public function applyApplicationStatusFilter(Builder $query, ?string $status): Builder
    {
        $filter = MaintenanceApplicationStatusFilterEnum::tryFromFilter($status);

        if ($filter === null) {
            return $query;
        }

        return match ($filter) {
            MaintenanceApplicationStatusFilterEnum::NO_PROFILE => $query->whereDoesntHave('studentProfile'),
            MaintenanceApplicationStatusFilterEnum::NO_PROGRAMMES => $query->whereHas(
                'studentProfile',
                fn (Builder $profile) => $profile->whereDoesntHave('programs'),
            ),
            MaintenanceApplicationStatusFilterEnum::REVIEW => $query->whereHas(
                'studentProfile.programs',
                fn (Builder $program) => $this->applyReviewProgrammeConstraint($program),
            ),
            MaintenanceApplicationStatusFilterEnum::WAITLISTED => $query->whereHas(
                'studentProfile.programs',
                fn (Builder $program) => $this->applyWaitlistedProgrammeConstraint($program),
            ),
            MaintenanceApplicationStatusFilterEnum::VERIFIED => $query->whereHas(
                'studentProfile.programs',
                fn (Builder $program) => $this->applyVerifiedProgrammeConstraint($program),
            ),
            MaintenanceApplicationStatusFilterEnum::UNKNOWN => $query
                ->whereHas(
                    'studentProfile.programs',
                    fn (Builder $program) => $program->whereNull('student_programs.deleted_at'),
                )
                ->whereDoesntHave(
                    'studentProfile.programs',
                    fn (Builder $program) => $program
                        ->whereNull('student_programs.deleted_at')
                        ->where(function (Builder $status): void {
                            $status->whereHas(
                                'departmentWorkflowStep.workflowStep',
                                fn (Builder $workflowStep) => $workflowStep->whereIn('slug', [
                                    WorkflowStepEnum::REVIEW->slug(),
                                    WorkflowStepEnum::WAITLISTED->slug(),
                                ]),
                            )->orWhereHas(
                                'classList',
                                fn (Builder $classList) => $classList->where(
                                    'type',
                                    ClassListTypeEnum::VERIFIED->value,
                                ),
                            );
                        }),
                ),
        };
    }

    private function applyReviewProgrammeConstraint(Builder $program): void
    {
        $program
            ->whereNull('student_programs.deleted_at')
            ->whereHas(
                'departmentWorkflowStep.workflowStep',
                fn (Builder $workflowStep) => $workflowStep->where(
                    'slug',
                    WorkflowStepEnum::REVIEW->slug(),
                ),
            );
    }

    private function applyWaitlistedProgrammeConstraint(Builder $program): void
    {
        $program
            ->whereNull('student_programs.deleted_at')
            ->whereHas(
                'departmentWorkflowStep.workflowStep',
                fn (Builder $workflowStep) => $workflowStep->where(
                    'slug',
                    WorkflowStepEnum::WAITLISTED->slug(),
                ),
            );
    }

    private function applyVerifiedProgrammeConstraint(Builder $program): void
    {
        $program
            ->whereNull('student_programs.deleted_at')
            ->whereHas(
                'classList',
                fn (Builder $classList) => $classList->where(
                    'type',
                    ClassListTypeEnum::VERIFIED->value,
                ),
            );
    }
}
