<?php

namespace App\Helpers;

use App\Models\Acl\RoleGroup;
use App\Support\Acl\PermissionRegistry;

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

        $permissions = collect(PermissionRegistry::allValues())
            ->reject(fn ($permission) => $excludedPermissions->contains($permission))
            ->mapWithKeys(fn ($permission) => [$permission => $permission]);
        $role->syncPermissions(array_values($permissions->toArray()));
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
}
