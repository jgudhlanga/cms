<?php

namespace App\Helpers;

use App\Models\Acl\Permission;
use App\Models\Acl\RoleGroup;
use App\Support\Acl\PermissionRegistry;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

class PermissionHelper
{
    public static function getGroupId($slug): mixed
    {
        $roleGroup = RoleGroup::where('slug', $slug)->first();

        return $roleGroup->id ?? null;
    }

    public static function assignSuperUserPermissions($role): void
    {
        $excludedPermissions = collect(array_merge(
            self::portalPermissions(),
            ['manageOwnData:tenants', 'viewOnlyOwnDepartment:departments']
        ));

        $permissionNames = collect(PermissionRegistry::allValues())
            ->reject(fn ($permission) => $excludedPermissions->contains($permission))
            ->values()
            ->all();

        $role->syncPermissions(self::resolvePermissions($permissionNames));
    }

    public static function portalPermissions(): array
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
        ];
    }

    /**
     * @return list<string>
     */
    public static function lecturerPermissions(): array
    {
        return [
            'view:lecturer-dashboard',
            'view:lecturer-classes',
            'view:lecturer-modules',
            'viewAny:course-work',
            'view:course-work',
            'update:course-work',
            'import:course-work',
            'view:academic-calendars',
        ];
    }

    /**
     * @param  array<int, string>  $permissionNames
     */
    public static function resolvePermissions(array $permissionNames): Collection
    {
        foreach ($permissionNames as $permissionName) {
            self::ensurePermissionExists($permissionName);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return Permission::query()
            ->whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->get();
    }

    public static function ensurePermissionExists(string $permissionName, string $guardName = 'web'): Permission
    {
        $permission = Permission::withTrashed()
            ->where('name', $permissionName)
            ->where('guard_name', $guardName)
            ->first();

        if ($permission instanceof Permission) {
            if ($permission->trashed()) {
                $permission->restore();
            }

            return $permission;
        }

        return Permission::query()->create([
            'name' => $permissionName,
            'guard_name' => $guardName,
        ]);
    }
}
