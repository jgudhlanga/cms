<?php

namespace App\Policies\Institution;

use App\Models\Users\User;

class DepartmentMetaDataPolicy
{
    public function viewAnyDepartmentMetaData(User $user): bool
    {
        return
            $user->can('root:manage') ||
            $user->can('viewAny:department-metadata');

    }

    public function viewDepartmentMetaData(User $user): bool
    {
        return $user->can('root:manage') ||
            $user->can('view:department-metadata') ||
            $user->can('viewOnlyOwnDepartment:departments');

    }

    public function createDepartmentMetaData(User $user): bool
    {
        return $user->can('root:manage') || $user->can('create:department-metadata');
    }

    public function updateDepartmentMetaData(User $user): bool
    {
        return $user->can('root:manage') || $user->can('update:department-metadata');
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
