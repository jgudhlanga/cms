<?php

namespace App\Policies\Students;

use App\Helpers\Helper;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use App\Services\Students\IntakePeriodResolver;

class StudentApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny:student-applications') ||
            $user->can('view:student-applications') ||
            $user->can('root:manage') ||
            $user->can('viewOnlyOwnDepartment:departments');
    }

    public function view(User $user, StudentApplication $studentApplication): bool
    {
        if ($user->can('viewAny:student-applications') ||
            $user->can('view:student-applications') ||
            $user->can('root:manage')) {
            return true;
        }

        if ($user->can('viewOnlyOwnDepartment:departments')) {
            return $this->userCanAccessApplicationDepartment($user, $studentApplication);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('create:student-applications');
    }

    public function update(User $user, StudentApplication $studentApplication): bool
    {
        if ($user->can('root:manage') || $user->can('manage:data-maintenance')) {
            return true;
        }

        if (! $user->can('update:student-applications')) {
            return false;
        }

        if (! app(IntakePeriodResolver::class)->isApplicationInActiveIntake($studentApplication)) {
            return false;
        }

        if ($this->isAcceptedApplication($studentApplication)) {
            return false;
        }

        return $this->userCanAccessApplicationDepartment($user, $studentApplication);
    }

    /**
     * Applicants upload proof of payment for their own application; workflow staff may upload for them.
     */
    public function uploadProofOfPayment(User $user, StudentApplication $studentApplication): bool
    {
        if ($this->manageWorkflow($user, $studentApplication)) {
            return true;
        }

        $studentId = $user->studentProfile?->id;

        return $studentId !== null
            && (int) $studentApplication->student_id === (int) $studentId
            && $user->can('manageOwnStudentApplicationDetails:students');
    }

    /**
     * Staff workflow actions (moving steps, confirming fees). Unlike update(), this deliberately
     * skips the active-intake and not-accepted checks so accepted applications can still progress.
     */
    public function manageWorkflow(User $user, StudentApplication $studentApplication): bool
    {
        if ($user->can('root:manage') || $user->can('manage:data-maintenance')) {
            return true;
        }

        return $user->can('update:student-applications')
            && $this->userCanAccessApplicationDepartment($user, $studentApplication);
    }

    /**
     * Bulk workflow actions across a department's applications.
     */
    public function manageDepartmentWorkflow(User $user, InstitutionDepartment $institutionDepartment): bool
    {
        if ($user->can('root:manage') || $user->can('manage:data-maintenance')) {
            return true;
        }

        if (! $user->can('update:student-applications')) {
            return false;
        }

        if ($user->can('viewAny:student-applications') || ! Helper::isDepartmentUser()) {
            return true;
        }

        $departments = array_map('intval', Helper::resolveUserDepartments() ?? []);

        return in_array((int) $institutionDepartment->id, $departments, true);
    }

    public function delete(User $user, StudentApplication $studentApplication): bool
    {
        if (! $user->can('delete:student-applications')) {
            return false;
        }

        return $this->userCanAccessApplicationDepartment($user, $studentApplication);
    }

    public function restore(User $user, StudentApplication $studentApplication): bool
    {
        if (! $user->can('restore:student-applications')) {
            return false;
        }

        return $this->userCanAccessApplicationDepartment($user, $studentApplication);
    }

    public function forceDelete(User $user, StudentApplication $studentApplication): bool
    {
        if (! $user->can('forceDelete:student-applications')) {
            return false;
        }

        return $this->userCanAccessApplicationDepartment($user, $studentApplication);
    }

    private function userCanAccessApplicationDepartment(User $user, StudentApplication $studentApplication): bool
    {
        if ($user->can('viewAny:student-applications') || $user->can('root:manage')) {
            return true;
        }

        if (! Helper::isDepartmentUser()) {
            return true;
        }

        $departments = Helper::resolveUserDepartments() ?? [];

        return in_array($studentApplication->institution_department_id, $departments, true);
    }

    private function isAcceptedApplication(StudentApplication $studentApplication): bool
    {
        $studentApplication->loadMissing('workflowStep');

        return $studentApplication->workflowStep?->name === 'Accepted';
    }
}
