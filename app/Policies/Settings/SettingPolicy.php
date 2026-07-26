<?php

namespace App\Policies\Settings;

use App\Models\Users\User;

class SettingPolicy
{
    public function viewSettings(User $user): bool
    {
        return $user->can('root:manage') || $user->can('view:settings');
    }
}
