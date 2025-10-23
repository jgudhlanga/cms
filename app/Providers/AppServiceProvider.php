<?php

namespace App\Providers;

use App\Enums\Acl\RoleEnum;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Add container bindings or singletons here if needed
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Disable JSON resource wrapping (no "data" key)
        JsonResource::withoutWrapping();

        // Dynamically register all Gate policies from config/custom.php
        $this->registerPoliciesFromConfig();

        // Track user login statistics
        $this->registerLoginEventListener();

        // Handle impersonation start and stop events
        $this->registerImpersonationListeners();

        // Restrict Log Viewer access
        $this->registerLogViewerAuthorization();
    }

    /**
     * Dynamically register Gates based on custom configuration.
     *
     * Example config/custom.php:
     * 'policies' => [
     *     App\Policies\UserPolicy::class => ['view', 'create', 'delete'],
     * ],
     */
    private function registerPoliciesFromConfig(): void
    {
        $policies = config('custom.policies', []);

        foreach ($policies as $policyClass => $abilities) {
            foreach ($abilities as $ability) {
                Gate::define($ability, [$policyClass, $ability]);
            }
        }
    }

    /**
     * Listen for login events and update user login metrics.
     */
    private function registerLoginEventListener(): void
    {
        Event::listen(Login::class, function ($event) {
            $user = $event->user;

            $user->update([
                'last_login_at' => now(),
                'login_count' => ($user->login_count ?? 0) + 1,
            ]);
        });
    }

    /**
     * Handle impersonation events (start and stop).
     */
    private function registerImpersonationListeners(): void
    {
        // When impersonation begins
        Event::listen(TakeImpersonation::class, function (TakeImpersonation $event) {
            session()->put([
                'password_hash_sanctum' => $event->impersonated->getAuthPassword(),
            ]);
        });

        // When impersonation ends
        Event::listen(LeaveImpersonation::class, function (LeaveImpersonation $event) {
            // Clean up and restore original user session
            session()->forget('password_hash_web');

            session()->put([
                'password_hash_sanctum' => $event->impersonator->getAuthPassword(),
            ]);

            // Ensure proper restoration of the impersonator in Auth context
            Auth::setUser($event->impersonator);
        });
    }

    /**
     * Restrict access to Log Viewer.
     * Only users with the SUPER_USER role can access it.
     */
    private function registerLogViewerAuthorization(): void
    {
        LogViewer::auth(function ($request) {
            return Auth::check()
                && Auth::user()->hasRole(RoleEnum::SUPER_USER->name());
        });
    }
}
