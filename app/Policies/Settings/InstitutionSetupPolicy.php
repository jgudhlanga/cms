<?php

namespace App\Policies\Settings;

use App\Models\Users\User;

class InstitutionSetupPolicy
{
    public function viewInstitutionSettings(User $user): bool
    {
        return $user->can('root:manage') || $user->can('view:institution-settings');
    }
}
