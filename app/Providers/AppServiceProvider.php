<?php

namespace App\Providers;

use App\Enums\Acl\RoleEnum;
use App\Importers\Finance\FinanceExchangeRateImporter;
use App\Importers\Institution\CourseSyllabusImporter;
use App\Importers\Institution\CourseSyllabusModuleImporter;
use App\JsonApi\V1\JsonApiAuthorizer;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Policies\AcademicCalendars\CourseWorkPolicy;
use App\Policies\Institution\CourseSyllabusPolicy;
use App\Support\Auth\SyncSessionPasswordHash;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;
use LaravelIngest\IngestServiceProvider;
use LaravelJsonApi\Laravel\LaravelJsonApi;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([
            FinanceExchangeRateImporter::class,
            CourseSyllabusImporter::class,
            CourseSyllabusModuleImporter::class,
        ], IngestServiceProvider::INGEST_DEFINITION_TAG);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn () => Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols());

        LaravelJsonApi::defaultAuthorizer(JsonApiAuthorizer::class);

        // Disable JSON resource wrapping (no "data" key)
        JsonResource::withoutWrapping();

        // Dynamically register all Gate policies from config/custom.php
        $this->registerPoliciesFromConfig();
        Gate::policy(CourseSyllabus::class, CourseSyllabusPolicy::class);
        Gate::policy(CourseWorkMark::class, CourseWorkPolicy::class);

        // Track user login statistics
        $this->registerLoginEventListener();

        // Handle impersonation start and stop events
        $this->registerImpersonationListeners();

        // Restrict Log Viewer access
        $this->registerLogViewerAuthorization();

        $this->registerLocalMailRedirect();
    }

    private function registerLocalMailRedirect(): void
    {
        if ($this->app->environment('local') && ($devEmail = config('mail.dev_redirect'))) {
            Mail::alwaysTo($devEmail);
        }
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

    private function registerImpersonationListeners(): void
    {
        Event::listen(TakeImpersonation::class, function (TakeImpersonation $event) {
            SyncSessionPasswordHash::forUser($event->impersonated);
        });

        Event::listen(LeaveImpersonation::class, function (LeaveImpersonation $event) {
            SyncSessionPasswordHash::forUser($event->impersonator);
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
