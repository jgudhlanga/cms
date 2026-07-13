<?php

namespace App\Http\Middleware;

use App\Enums\Acl\RoleEnum;
use App\Helpers\PermissionHelper;
use App\Http\Resources\Users\UserResource;
use App\Models\Users\User;
use App\Services\Acl\AclModuleStateService;
use App\Services\Students\RegistrationAvailabilityService;
use App\Services\Students\ReturningStudentContextService;
use App\Support\Acl\PermissionRegistry;
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
        // [$message, $author] = str(Inspiring::quotes()->random())->explode('-');
        $user = $request->user();
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
            // 'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $user ? new UserResource($user) : null,
                'can' => $user ? $this->permissions($user) : null,
                'impersonating' => $isImpersonating,
            ],
            'moduleState' => fn () => app(AclModuleStateService::class)->all(),
            'registration' => fn () => [
                'isOpen' => app(RegistrationAvailabilityService::class)->isRegistrationOpen(),
                'status' => app(RegistrationAvailabilityService::class)->blockReason()?->value,
                'maintenanceUrl' => route('portal.registration.maintenance'),
            ],
            'returningStudent' => fn () => $this->returningStudentProps($user),
            'purgeArchiveRetentionDays' => (int) config('purge.archive_retention_days', 30),
            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    private function permissions(User $user): Collection
    {
        // If user is super admin, return all permissions as true
        if ($user->hasRole(RoleEnum::SUPER_USER->name())) {
            return collect(PermissionRegistry::allValues())
                ->reject(fn (string $permission) => $this->isExcludedPermission($permission))
                ->mapWithKeys(fn ($permission) => [$permission => true]);
        }

        $permissions = $user->getAllPermissions()
            ->pluck('name')
            ->flip()
            ->map(fn () => true);

        return $this->mergePortalStudentAbilities($user, $permissions);
    }

    private function mergePortalStudentAbilities(User $user, Collection $permissions): Collection
    {
        if ($user->studentProfile === null) {
            return $permissions;
        }

        foreach (PermissionHelper::portalPermissions() as $portalPermission) {
            if ($user->can($portalPermission)) {
                $permissions->put($portalPermission, true);
            }
        }

        // Portal students with profile access should always see accommodation self-service.
        if ($user->can('manageOwnStudentPersonalDetails:students')) {
            $permissions->put('manageOwnStudentAccommodationDetails:students', true);
        }

        return $permissions;
    }

    private function excludePermissions(): array
    {
        return [
            'viewOwnDashboard:students',
            'manageOwnStudentPersonalDetails:students',
            'manageOwnStudentApplicationDetails:students',
            'manageOwnStudentSponsorDetails:students',
            'manageOwnStudentContactDetails:students',
            'manageOwnStudentFinancialDetails:students',
            'manageOwnStudentAcademicDetails:students',
            'manageOwnStudentAccommodationDetails:students',
            'view:next-of-kins',
            'create:next-of-kins',
            'update:next-of-kins',
            'delete:next-of-kins',
            'forceDelete:next-of-kins',
            'viewOnlyOwnDepartment:departments',
            'manageOwnData:tenants',
        ];
    }

    private function isExcludedPermission(string $permission): bool
    {
        if (in_array($permission, $this->excludePermissions(), true)) {
            return true;
        }

        return false;
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
