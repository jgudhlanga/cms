<?php

namespace App\Policies\Settings;

use App\Enums\Acl\PermissionEnum;
use App\Models\Users\User;

class InstitutionSetupPolicy
{
    public function viewInstitutionSettings(User $user): bool
    {
        return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::VIEW_INSTITUTION_SETTINGS);

    }

    public function createInstitutionSettings(User $user): bool
    {
        return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::CREATE_INSTITUTION_SETTINGS);
    }

    public function updateInstitutionSettings(User $user): bool
    {
        return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::UPDATE_INSTITUTION_SETTINGS);
    }

    public function deleteInstitutionSettings(User $user): bool
    {

        return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::DELETE_INSTITUTION_SETTINGS);
    }

    public function restoreInstitutionSettings(User $user): bool
    {
        return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::RESTORE_INSTITUTION_SETTINGS);
    }

    public function forceDeleteInstitutionSettings(User $user): bool
    {
        return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::FORCE_DELETE_INSTITUTION_SETTINGS);
    }

    public function importInstitutionSettings(User $user): bool
    {
        return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::IMPORT_INSTITUTION_SETTINGS);
    }

    public function exportInstitutionSettings(User $user): bool
    {
        return $user->can(PermissionEnum::ROOT_MANAGE) || $user->can(PermissionEnum::EXPORT_INSTITUTION_SETTINGS);
    }
}
