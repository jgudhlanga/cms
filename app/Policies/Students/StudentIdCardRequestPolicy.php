<?php

declare(strict_types=1);

namespace App\Policies\Students;

use App\Enums\Shared\ModuleEnum;
use App\Models\Students\Student;
use App\Models\Students\StudentIdCardRequest;
use App\Models\Users\User;
use App\Services\Rbac\RbacModuleStateService;

class StudentIdCardRequestPolicy
{
    public function __construct(
        private readonly RbacModuleStateService $moduleState,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->moduleEnabled() && $user->can('viewAny:student-id-card-requests');
    }

    public function view(User $user, StudentIdCardRequest $studentIdCardRequest): bool
    {
        if (! $this->moduleEnabled()) {
            return false;
        }

        if ($this->ownsRequest($user, $studentIdCardRequest)) {
            return $user->can('manageOwnStudentPersonalDetails:students');
        }

        return $user->can('viewAny:student-id-card-requests')
            || $user->can('view:student-id-card-requests');
    }

    public function create(User $user): bool
    {
        return $this->moduleEnabled()
            && $user->studentProfile !== null
            && $user->can('manageOwnStudentPersonalDetails:students');
    }

    public function uploadPhoto(User $user, ?Student $student = null): bool
    {
        if (! $this->moduleEnabled()) {
            return false;
        }

        if ($student instanceof Student) {
            if ($user->studentProfile?->id === $student->id) {
                return $user->can('manageOwnStudentPersonalDetails:students');
            }

            return $user->can('uploadIdPhoto:students') || $user->can('update:students');
        }

        return ($user->studentProfile !== null && $user->can('manageOwnStudentPersonalDetails:students'))
            || $user->can('uploadIdPhoto:students')
            || $user->can('update:students');
    }

    public function import(User $user): bool
    {
        return $this->print($user);
    }

    public function review(User $user, ?StudentIdCardRequest $studentIdCardRequest = null): bool
    {
        return $this->moduleEnabled() && $user->can('review:student-id-card-requests');
    }

    public function print(User $user, ?StudentIdCardRequest $studentIdCardRequest = null): bool
    {
        return $this->moduleEnabled() && $user->can('print:student-id-card-requests');
    }

    public function export(User $user): bool
    {
        return $this->print($user);
    }

    public function issue(User $user, ?StudentIdCardRequest $studentIdCardRequest = null): bool
    {
        return $this->moduleEnabled() && $user->can('issue:student-id-card-requests');
    }

    public function viewAuditTrail(User $user, ?StudentIdCardRequest $studentIdCardRequest = null): bool
    {
        return $this->moduleEnabled() && $user->can('viewAuditTrail:student-id-card-requests');
    }

    private function moduleEnabled(): bool
    {
        return $this->moduleState->isEnabled(ModuleEnum::STUDENT_IDS->slug());
    }

    private function ownsRequest(User $user, StudentIdCardRequest $studentIdCardRequest): bool
    {
        return $user->studentProfile?->id === $studentIdCardRequest->student_id;
    }
}
