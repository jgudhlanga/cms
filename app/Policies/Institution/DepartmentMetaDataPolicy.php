<?php

namespace App\Policies\Institution;

use App\Models\Institution\InstitutionDepartment;
use App\Models\Users\User;
use App\Support\Rbac\UserAccessScope;

class DepartmentMetaDataPolicy
{
    /**
     * Department-scoped users (HODs and the like) may only act on the departments they are
     * assigned to. Callers that pass no department are unaffected, so abilities used for
     * college-wide screens keep working unchanged.
     */
    private function canReachDepartment(User $user, ?InstitutionDepartment $department): bool
    {
        if (! $department instanceof InstitutionDepartment) {
            return true;
        }

        if ($user->can('root:manage')) {
            return true;
        }

        $permitted = UserAccessScope::for($user)->departmentIds();

        // Null means unrestricted (college-wide); an empty array means no accessible departments.
        return $permitted === null || in_array((int) $department->id, $permitted, true);
    }

    public function viewAnyDepartmentMetaData(User $user): bool
    {
        return
            $user->can('root:manage') ||
            $user->can('viewAny:department-metadata');

    }

    public function viewDepartmentMetaData(User $user, ?InstitutionDepartment $department = null): bool
    {
        $allowed = $user->can('root:manage') ||
            $user->can('view:department-metadata') ||
            $user->can('viewOnlyOwnDepartment:departments');

        return $allowed && $this->canReachDepartment($user, $department);
    }

    public function createDepartmentMetaData(User $user): bool
    {
        return $user->can('root:manage') || $user->can('create:department-metadata');
    }

    public function updateDepartmentMetaData(User $user, ?InstitutionDepartment $department = null): bool
    {
        $allowed = $user->can('root:manage') || $user->can('update:department-metadata');

        return $allowed && $this->canReachDepartment($user, $department);
    }

    public function deleteDepartmentMetaData(User $user): bool
    {

        return $user->can('root:manage') || $user->can('delete:department-metadata');
    }

    public function restoreDepartmentMetaData(User $user): bool
    {
        return $user->can('root:manage') || $user->can('restore:department-metadata');
    }

    public function forceDeleteDepartmentMetaData(User $user): bool
    {
        return $user->can('root:manage') || $user->can('forceDelete:department-metadata');
    }

    public function importDepartmentMetaData(User $user): bool
    {
        return $user->can('root:manage') || $user->can('import:department-metadata');
    }

    public function exportDepartmentMetaData(User $user): bool
    {
        return $user->can('root:manage') || $user->can('export:department-metadata');
    }
}
