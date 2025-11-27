<?php

namespace App\Http\Middleware;

use App\Enums\Acl\PermissionEnum;
use App\Enums\Acl\RoleEnum;
use App\Http\Resources\Users\UserResource;
use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;
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
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        //[$message, $author] = str(Inspiring::quotes()->random())->explode('-');
        $user = $request->user();
        $impersonate = app(ImpersonateManager::class);
        $isImpersonating = $impersonate->isImpersonating();
        return [
            ...parent::share($request),
            'name' => config('app.name'),
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
            'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    private function permissions(User $user): Collection
    {
        // If user is super admin, return all permissions as true
        if ($user->hasRole(RoleEnum::SUPER_USER->name())) {
            return collect(PermissionEnum::cases())
                ->reject(fn($permission) => in_array($permission->value, $this->excludePermissions(), true))
                ->mapWithKeys(fn($permission) => [$permission->value => true]);
        }

        // Otherwise, return only the user's assigned permissions
        return $user?->getAllPermissions()
            ->pluck('name')
            ->flip()
            ->map(fn() => true);
    }

    private function excludePermissions(): array
    {
        return [
            PermissionEnum::VIEW_OWN_STUDENT_DASHBOARD->value,
            PermissionEnum::MANAGE_OWN_STUDENT_PERSONAL_DETAILS->value,
            PermissionEnum::MANAGE_OWN_STUDENT_PROGRAM_DETAILS->value,
            PermissionEnum::MANAGE_OWN_STUDENT_SPONSOR_DETAILS->value,
            PermissionEnum::MANAGE_OWN_STUDENT_CONTACT_DETAILS->value,
            PermissionEnum::MANAGE_OWN_STUDENT_FINANCIAL_DETAILS->value,
            PermissionEnum::MANAGE_OWN_STUDENT_ACADEMIC_DETAILS->value,
            PermissionEnum::VIEW_NEXT_OF_KINS->value,
            PermissionEnum::CREATE_NEXT_OF_KINS->value,
            PermissionEnum::UPDATE_NEXT_OF_KINS->value,
            PermissionEnum::DELETE_NEXT_OF_KINS->value,
            PermissionEnum::FORCE_DELETE_NEXT_OF_KINS->value,
            PermissionEnum::VIEW_ONLY_OWN_DEPARTMENT->value,
            PermissionEnum::MANAGE_OWN_TENANT_DATA->value
        ];
    }
}
