<?php

namespace App\Http\Middleware;

use App\Http\Resources\Users\UserResource;
use App\Models\Users\User;
use App\Services\Rbac\RbacModuleStateService;
use App\Services\Rbac\UserPermissionMapService;
use App\Services\Students\RegistrationAvailabilityService;
use App\Services\Students\ReturningStudentContextService;
use App\Support\AppVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Middleware;
use Lab404\Impersonate\Services\ImpersonateManager;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user instanceof User) {
            $this->eagerLoadAuthRelations($user);
        }

        $impersonate = app(ImpersonateManager::class);
        $isImpersonating = $impersonate->isImpersonating();

        $appearance = $request->cookie('appearance') ?? 'system';
        $systemPrefersDark = strcasecmp((string) $request->header('Sec-CH-Prefers-Color-Scheme', ''), 'dark') === 0;

        return [
            ...parent::share($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
            'name' => config('app.name'),
            'displayName' => config('app.display_name'),
            'appEnv' => config('app.env'),
            'appVersion' => app(AppVersion::class)->resolve(),
            'appearance' => [
                'preference' => $appearance,
                'systemPrefersDark' => $systemPrefersDark,
            ],
            'auth' => [
                'user' => $user ? new UserResource($user) : null,
                'can' => $user ? $this->permissions($user) : null,
                'impersonating' => $isImpersonating,
            ],
            'moduleState' => fn () => app(RbacModuleStateService::class)->all(),
            'registration' => fn () => app(RegistrationAvailabilityService::class)->sharedProps(),
            'returningStudent' => fn () => $this->returningStudentProps($user),
            'purgeArchiveRetentionDays' => (int) config('purge.archive_retention_days', 30),
            'ziggy' => [
                ...(new Ziggy)->filter([
                    '!*telescope*',
                    '!*horizon*',
                    '!*debugbar*',
                    '!*log-viewer*',
                    '!sanctum.*',
                ])->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    private function eagerLoadAuthRelations(User $user): void
    {
        $user->loadMissing([
            'roles',
            'permissions',
            'studentProfile',
            'staffProfile',
            'tenant',
            'status',
        ]);
    }

    /**
     * @return Collection<string, bool>
     */
    private function permissions(User $user): Collection
    {
        return app(UserPermissionMapService::class)->forUser($user);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function returningStudentProps(?User $user): ?array
    {
        $student = $user?->studentProfile;

        if ($student === null) {
            return null;
        }

        return app(ReturningStudentContextService::class)->toInertiaProps($student);
    }
}
