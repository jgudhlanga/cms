<?php

namespace Database\Seeders\Rbac;

use App\Enums\Rbac\RoleEnum;
use App\Helpers\PermissionHelper;
use App\Models\Rbac\Role;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RoleEnum::cases() as $row) {
            $exist = Role::where('name', $row->name())->withTrashed()->first();
            if (! $exist instanceof Role) {
                $role = Role::create(
                    [
                        'name' => $row->name(),
                        'role_group_id' => PermissionHelper::getGroupId($row->group()),
                        'description' => $row->description(),
                    ]);
                if ($role->name == RoleEnum::SUPER_USER->name()) {
                    PermissionHelper::assignSuperUserPermissions($role);
                }
                if ($role->name == RoleEnum::STUDENT->name()) {
                    $role->syncPermissions(PermissionHelper::resolvePermissions(PermissionHelper::portalPermissions()));
                }
                if (in_array($role->name, self::lecturerRoleNames(), true)) {
                    $role->syncPermissions(PermissionHelper::resolvePermissions(PermissionHelper::lecturerPermissions()));
                }
            } else {
                if ($exist->name == RoleEnum::SUPER_USER->name()) {
                    PermissionHelper::assignSuperUserPermissions($exist);
                }
                if ($exist->name == RoleEnum::STUDENT->name()) {
                    $exist->syncPermissions(PermissionHelper::resolvePermissions(PermissionHelper::portalPermissions()));
                }
                if (in_array($exist->name, self::lecturerRoleNames(), true)) {
                    $exist->syncPermissions(PermissionHelper::resolvePermissions(PermissionHelper::lecturerPermissions()));
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    private static function lecturerRoleNames(): array
    {
        return [
            RoleEnum::LECTURER->name(),
            RoleEnum::SENIOR_LECTURER->name(),
            RoleEnum::LECTURER_IN_CHARGE->name(),
        ];
    }
}
