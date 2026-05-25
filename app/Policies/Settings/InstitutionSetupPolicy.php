<?php

namespace App\Policies\Settings;

use App\Models\Users\User;

class InstitutionSetupPolicy
{
    public function viewInstitutionSettings(User $user): bool
    {
        return $user->can('root:manage') || $user->can('view:institution-settings');

    }

    public function createInstitutionSettings(User $user): bool
    {
        return $user->can('root:manage') || $user->can('create:institution-settings');
    }

    public function updateInstitutionSettings(User $user): bool
    {
        return $user->can('root:manage') || $user->can('update:institution-settings');
    }

    public function deleteInstitutionSettings(User $user): bool
    {

        return $user->can('root:manage') || $user->can('delete:institution-settings');
    }

    public function restoreInstitutionSettings(User $user): bool
    {
        return $user->can('root:manage') || $user->can('restore:institution-settings');
    }

    public function forceDeleteInstitutionSettings(User $user): bool
    {
        return $user->can('root:manage') || $user->can('forceDelete:institution-settings');
    }

    public function importInstitutionSettings(User $user): bool
    {
        return $user->can('root:manage') || $user->can('import:institution-settings');
    }

    public function exportInstitutionSettings(User $user): bool
    {
        return $user->can('root:manage') || $user->can('export:institution-settings');
    }
}
