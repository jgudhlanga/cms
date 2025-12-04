<?php

namespace App\Helpers;

use App\Enums\Acl\PermissionEnum;
use App\Models\Acl\RoleGroup;

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
            [PermissionEnum::MANAGE_OWN_TENANT_DATA->value, PermissionEnum::VIEW_ONLY_OWN_DEPARTMENT->value]
        ));
        $permissions = collect(PermissionEnum::cases())
            ->reject(fn($case) => $excludedPermissions->contains($case->value))
            ->mapWithKeys(fn($case) => [$case->value => $case->value]);
        $role->syncPermissions(array_values($permissions->toArray()));
    }

    public static function portalPermissions(): array
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
        ];
    }
}
