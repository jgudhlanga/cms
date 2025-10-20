<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        JsonResource::withoutWrapping();

        $this->registerPoliciesFromConfig();

        Event::listen(Login::class, function ($event) {
            $user = $event->user;
            $user->update([
                'last_login_at' => now(),
                'login_count' => ($user->login_count ?? 0) + 1,
            ]);
        });

        // When impersonation begins
        Event::listen(function (TakeImpersonation $event) {
            session()->put([
                'password_hash_sanctum' => $event->impersonated->getAuthPassword(),
            ]);
        });

        // When impersonation ends
        Event::listen(function (LeaveImpersonation $event) {
            session()->forget('password_hash_web');
            session()->put([
                'password_hash_sanctum' => $event->impersonator->getAuthPassword(),
            ]);

            // Ensure proper user restoration
            Auth::setUser($event->impersonator);
        });
    }

    private function registerPoliciesFromConfig(): void
    {
        $policies = config('custom.policies');

        foreach ($policies as $policyClass => $abilities) {
            foreach ($abilities as $ability) {
                Gate::define($ability, [$policyClass, $ability]);
            }
        }
    }
}
