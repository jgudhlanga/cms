<?php

declare(strict_types=1);

namespace App\Queries\Maintenance;

use App\Enums\Acl\RoleEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\FeeTypeEnum;
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
            ->whereDoesntHave(
                'ledgerTransactions',
                fn (Builder $ledger) => $ledger
                    ->where('type', 'receipt')
                    ->where('payment_status', 'paid')
                    ->whereHas(
                        'feeType',
                        fn (Builder $feeType) => $feeType->whereIn('slug', [
                            FeeTypeEnum::APPLICATION_FEE->slug(),
                            FeeTypeEnum::TUITION_FEE->slug(),
                        ]),
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
}
