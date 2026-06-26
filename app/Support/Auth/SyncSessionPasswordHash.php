<?php

namespace App\Support\Auth;

use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class SyncSessionPasswordHash
{
    public static function forUser(?Authenticatable $user, ?string $guard = null): void
    {
        if ($user === null) {
            return;
        }

        $guard = $guard ?? config('auth.defaults.guard', 'web');
        $guardInstance = Auth::guard($guard);

        if (! $guardInstance instanceof SessionGuard) {
            return;
        }

        session()->put([
            "password_hash_{$guard}" => method_exists($guardInstance, 'hashPasswordForCookie')
                ? $guardInstance->hashPasswordForCookie($user->getAuthPassword())
                : $user->getAuthPassword(),
        ]);
    }
}
