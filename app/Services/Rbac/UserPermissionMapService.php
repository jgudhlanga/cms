<?php

namespace App\Services\Rbac;

use App\Enums\Rbac\RoleEnum;
use App\Helpers\PermissionHelper;
use App\Models\Users\User;
use App\Support\Rbac\PermissionRegistry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class UserPermissionMapService
{
    private const string CACHE_PREFIX = 'user_permission_map:';

    private const string VERSION_KEY = 'user_permission_map_version';

    private const int CACHE_TTL_SECONDS = 300;

    /**
     * @return Collection<string, bool>
     */
    public function forUser(User $user): Collection
    {
        /** @var array<string, bool> $map */
        $map = Cache::remember($this->cacheKey($user->id), self::CACHE_TTL_SECONDS, function () use ($user): array {
            return $this->buildMap($user)->all();
        });

        return collect($map);
    }

    public function forgetForUser(int $userId): void
    {
        Cache::forget($this->cacheKey($userId));
    }

    public function forgetForUsers(iterable $userIds): void
    {
        foreach ($userIds as $userId) {
            $this->forgetForUser((int) $userId);
        }
    }

    /**
     * Bust all cached maps when role permission definitions change.
     */
    public function flushAll(): void
    {
        Cache::forever(self::VERSION_KEY, $this->version() + 1);
    }

    private function cacheKey(int $userId): string
    {
        return self::CACHE_PREFIX.'v'.$this->version().':'.$userId;
    }

    private function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 0);
    }

    /**
     * @return Collection<string, bool>
     */
    private function buildMap(User $user): Collection
    {
        if ($user->hasRole(RoleEnum::SUPER_USER->name())) {
            return collect(PermissionRegistry::allValues())
                ->reject(fn (string $permission) => $this->isExcludedPermission($permission))
                ->mapWithKeys(fn (string $permission) => [$permission => true]);
        }

        $permissions = $user->getAllPermissions()
            ->pluck('name')
            ->flip()
            ->map(fn () => true);

        return $this->mergePortalStudentAbilities($user, $permissions);
    }

    /**
     * @param  Collection<string, bool>  $permissions
     * @return Collection<string, bool>
     */
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

        if ($user->can('manageOwnStudentPersonalDetails:students')) {
            $permissions->put('manageOwnStudentAccommodationDetails:students', true);
        }

        return $permissions;
    }

    private function isExcludedPermission(string $permission): bool
    {
        if (in_array($permission, [
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
            'view:lecturer-dashboard',
            'view:lecturer-classes',
            'view:lecturer-modules',
        ], true)) {
            return true;
        }

        return str($permission)->endsWith([':finances', ':finance-settings']);
    }
}
