<?php

namespace App\Policies\Institution;

use App\Enums\PermissionEnum;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Users\User;

class InstitutionDepartmentPolicy
{

    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_INSTITUTION_DEPARTMENTS);
    }

    public function view(User $user, InstitutionDepartment $institutionDepartment): bool
    {
        return $user->can(PermissionEnum::VIEW_ANY_INSTITUTION_DEPARTMENTS) || $user->can(PermissionEnum::VIEW_INSTITUTION_DEPARTMENTS);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::CREATE_INSTITUTION_DEPARTMENTS);
    }

    public function update(User $user, InstitutionDepartment $institutionDepartment): bool
    {
        return $user->can(PermissionEnum::UPDATE_INSTITUTION_DEPARTMENTS, $institutionDepartment);
    }

    public function delete(User $user, InstitutionDepartment $institutionDepartment): bool
    {
        return $user->can(PermissionEnum::DELETE_INSTITUTION_DEPARTMENTS, $institutionDepartment);
    }

    public function restore(User $user, InstitutionDepartment $institutionDepartment): bool
    {
        return $user->can(PermissionEnum::RESTORE_INSTITUTION_DEPARTMENTS, $institutionDepartment);
    }

    public function forceDelete(User $user, InstitutionDepartment $institutionDepartment): bool
    {
        return $user->can(PermissionEnum::FORCE_DELETE_INSTITUTION_DEPARTMENTS, $institutionDepartment);
    }
}
