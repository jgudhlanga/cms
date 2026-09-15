<?php

namespace App\Providers;

use App\Contracts\Students\StudentIdCardPrinter;
use App\Enums\Rbac\RoleEnum;
use App\Importers\Finance\FinanceExchangeRateImporter;
use App\Importers\Institution\CourseSyllabusImporter;
use App\Importers\Institution\CourseSyllabusModuleImporter;
use App\JsonApi\V1\JsonApiAuthorizer;
use App\Models\AcademicCalendars\CourseWorkCaptureExtension;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\AcademicCalendars\CourseWorkProgressReport;
use App\Models\Examinations\ExaminationResult;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentCalendar\DepartmentAssessmentCalendar;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Users\User;
use App\Policies\AcademicCalendars\CourseWorkCaptureExtensionPolicy;
use App\Policies\AcademicCalendars\CourseWorkPolicy;
use App\Policies\AcademicCalendars\CourseWorkProgressReportPolicy;
use App\Policies\Examinations\ExaminationPolicy;
use App\Policies\Institution\AssessmentCalendarPolicy;
use App\Policies\Institution\CourseSyllabusPolicy;
use App\Policies\Institution\DepartmentAssessmentCalendarPolicy;
use App\Services\Students\ApplicationFeeService;
use App\Services\Students\PdfCardPrinter;
use App\Services\Students\PhysicalCardPrinter;
use App\Support\Auth\SyncSessionPasswordHash;
use App\Support\Rbac\UserAccessScope;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
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

        $this->app->bind(StudentIdCardPrinter::class, function (): StudentIdCardPrinter {
            return match (config('id_cards.printer.driver')) {
                'physical' => new PhysicalCardPrinter,
                default => new PdfCardPrinter,
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureLazyLoadingDetection();

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
        Gate::policy(AssessmentCalendar::class, AssessmentCalendarPolicy::class);
        Gate::policy(DepartmentAssessmentCalendar::class, DepartmentAssessmentCalendarPolicy::class);
        Gate::policy(CourseWorkCaptureExtension::class, CourseWorkCaptureExtensionPolicy::class);
        Gate::policy(CourseWorkProgressReport::class, CourseWorkProgressReportPolicy::class);
        Gate::policy(ExaminationResult::class, ExaminationPolicy::class);

        // Track user login statistics
        $this->registerLoginEventListener();

        // Handle impersonation start and stop events
        $this->registerImpersonationListeners();

        // Restrict Log Viewer access
        $this->registerLogViewerAuthorization();

        $this->registerDataMaintenanceGate();

        // Per-request lookups must not carry into the next request handled by the same process.
        Event::listen(RequestHandled::class, function (): void {
            UserAccessScope::flush();
            ApplicationFeeService::forgetOpenIntakePeriodsForPortal();
        });

        $this->registerRateLimiters();

        $this->registerLocalMailRedirect();
    }

    /**
     * Lazy loading throws locally. In production each model/relation violation is logged at most
     * once an hour instead, so N+1 queries show up in the logs without breaking pages.
     */
    private function configureLazyLoadingDetection(): void
    {
        if ($this->app->environment('local')) {
            Model::preventLazyLoading();

            return;
        }

        if (! $this->app->isProduction()) {
            return;
        }

        Model::preventLazyLoading();
        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
            static $reported = [];

            $key = 'lazy-loading-violation:'.$model::class.':'.$relation;

            if (isset($reported[$key])) {
                return;
            }

            $reported[$key] = true;

            if (Cache::add($key, true, now()->addHour())) {
                Log::warning('Lazy loading detected', [
                    'model' => $model::class,
                    'relation' => $relation,
                    'url' => app()->runningInConsole() ? null : request()->fullUrl(),
                ]);
            }
        });
    }

    private function registerDataMaintenanceGate(): void
    {
        Gate::define('accessDataMaintenance', function (User $user): bool {
            return $user->can('root:manage') || $user->can('manage:data-maintenance');
        });
    }

    /**
     * Rate limiters for the API group, credential endpoints and public lookups.
     */
    private function registerRateLimiters(): void
    {
        // Generous: a single form can fire many combobox lookups at once.
        RateLimiter::for('api', function (Request $request): Limit {
            $userId = $request->user()?->getAuthIdentifier();

            return Limit::perMinute(300)->by($userId !== null ? 'user:'.$userId : 'ip:'.$request->ip());
        });

        // Per-IP limits stay generous: campus users often share one public IP.
        RateLimiter::for('auth-api', function (Request $request): array {
            return [
                Limit::perMinute(5)->by('credentials:'.Str::lower((string) $request->input('email')).'|'.$request->ip()),
                Limit::perMinute(60)->by('ip:'.$request->ip()),
            ];
        });

        // Unauthenticated lookups used by the public website and registration forms.
        RateLimiter::for('public-lookups', function (Request $request): Limit {
            $userId = $request->user()?->getAuthIdentifier();

            return $userId !== null
                ? Limit::perMinute(300)->by('user:'.$userId)
                : Limit::perMinute(600)->by('ip:'.$request->ip());
        });
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
