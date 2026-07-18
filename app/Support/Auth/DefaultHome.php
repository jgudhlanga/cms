<?php

namespace App\Support\Auth;

use App\Models\Users\User;

class DefaultHome
{
    public static function routeName(User $user): string
    {
        return 'dashboard';
    }
}
