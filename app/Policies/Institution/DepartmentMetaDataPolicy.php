<?php

namespace App\Policies\Institution;

use App\Enums\PermissionEnum;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Users\User;

class DepartmentMetaDataPolicy
{

    public function viewAnyDepartmentMetaData(User $user): bool
    {
        return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::VIEW_ANY_DEPARTMENT_METADATA);

    }

    public function viewDepartmentMetaData(User $user): bool
    {
        return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::VIEW_DEPARTMENT_METADATA);

    }

    public function createDepartmentMetaData(User $user): bool
    {
        return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::CREATE_DEPARTMENT_METADATA);
    }

    public function updateDepartmentMetaData(User $user): bool
    {
        return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::UPDATE_DEPARTMENT_METADATA);
    }

    public function deleteDepartmentMetaData(User $user): bool
    {

        return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::DELETE_DEPARTMENT_METADATA);
    }

    public function restoreDepartmentMetaData(User $user): bool
    {
        return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::RESTORE_DEPARTMENT_METADATA);
    }

    public function forceDeleteDepartmentMetaData(User $user): bool
    {
        return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::FORCE_DELETE_DEPARTMENT_METADATA);
    }

    public function importDepartmentMetaData(User $user): bool
    {
        return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::IMPORT_DEPARTMENT_METADATA);
    }

    public function exportDepartmentMetaData(User $user): bool
    {
        return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::EXPORT_DEPARTMENT_METADATA);
    }
}
