<?php

namespace App\Http\Middleware;

use App\Enums\Rbac\RoleEnum;
use App\Http\Resources\Users\UserResource;
use App\Models\Users\User;
use App\Services\Rbac\RbacModuleStateService;
use App\Services\Rbac\UserPermissionMapService;
use App\Services\Students\RegistrationAvailabilityService;
use App\Support\AppVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Middleware;
use Lab404\Impersonate\Services\ImpersonateManager;

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
     * These run on every navigation, so anything that touches the database or cache is a closure
     * and is skipped by partial reloads. The route list is not shared: @routes renders it.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $user = $user instanceof User ? $user : null;

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
            'auth' => fn () => [
                'user' => $user !== null ? UserResource::forSharedProps($this->loadAuthRelations($user)) : null,
                'can' => $user !== null ? $this->permissions($user) : null,
                'impersonating' => app(ImpersonateManager::class)->isImpersonating(),
            ],
            'moduleState' => fn () => app(RbacModuleStateService::class)->all(),
            'notifications' => fn () => $user !== null ? ['unreadCount' => $user->unreadNotifications()->count()] : null,
            'registration' => fn () => $this->sharesRegistration($user)
                ? app(RegistrationAvailabilityService::class)->sharedProps()
                : null,
            'purgeArchiveRetentionDays' => (int) config('purge.archive_retention_days', 30),
        ];
    }

    private function loadAuthRelations(User $user): User
    {
        return $user->loadMissing([
            'roles',
            'permissions',
            'studentProfile',
            'staffProfile',
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
     * Registration availability is only read by guest and applicant/student pages; staff pages fall
     * back to the client-side default.
     */
    private function sharesRegistration(?User $user): bool
    {
        return $user === null || $user->hasRole(RoleEnum::STUDENT->name());
    }
}
