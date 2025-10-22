<?php

namespace App\Http\Middleware;

use App\Enums\Acl\PermissionEnum;
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
            /* 'ziggy' => [
                 ...(new Ziggy)->toArray(),
                 'location' => $request->url(),
             ],*/
            'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Maps the user's permissions to their names as keys and a boolean for whether the user can perform the permission.
     *
     * @param User $user
     * @return Collection<PermissionEnum, bool>
     */
    private function permissions(User $user): Collection
    {
        return $user?->getAllPermissions()->pluck('name')->flip()->map(fn() => true);
    }
}
