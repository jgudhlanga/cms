<?php

namespace App\Http\Middleware;

use App\Enums\Acl\RoleEnum;
use App\Http\Resources\Users\UserResource;
use App\Models\Users\User;
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
            'name' => config('app.name'),
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

        // Otherwise, return only the user's assigned permissions
        return $user?->getAllPermissions()
            ->pluck('name')
            ->flip()
            ->map(fn () => true);
    }

    private function excludePermissions(): array
    {
        return [
            'viewOwnDashboard:students',
            'manageOwnStudentPersonalDetails:students',
            'manageOwnStudentProgramDetails:students',
            'manageOwnStudentSponsorDetails:students',
            'manageOwnStudentContactDetails:students',
            'manageOwnStudentFinancialDetails:students',
            'manageOwnStudentAcademicDetails:students',
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
}
