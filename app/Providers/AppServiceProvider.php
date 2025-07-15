<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
