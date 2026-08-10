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
                $role = Role::create([
                    'name' => $row->name(),
                    'role_group_id' => PermissionHelper::getGroupId($row->group()),
                    'description' => $row->description(),
                ]);
                $this->syncRolePermissions($role);
            } else {
                $exist->update([
                    'role_group_id' => PermissionHelper::getGroupId($row->group()),
                    'description' => $row->description(),
                ]);
                $this->syncRolePermissions($exist);
            }
        }
    }

    private function syncRolePermissions(Role $role): void
    {
        if ($role->name === RoleEnum::SUPER_USER->name()) {
            PermissionHelper::assignSuperUserPermissions($role);

            return;
        }

        $pack = $this->permissionPackForRoleName($role->name);

        if ($pack === null) {
            return;
        }

        $role->syncPermissions(PermissionHelper::resolvePermissions($pack));
    }

    /**
     * @return list<string>|null
     */
    private function permissionPackForRoleName(string $roleName): ?array
    {
        return match ($roleName) {
            RoleEnum::STUDENT->name() => PermissionHelper::portalPermissions(),
            RoleEnum::LECTURER->name(),
            RoleEnum::SENIOR_LECTURER->name(),
            RoleEnum::LECTURER_IN_CHARGE->name() => PermissionHelper::lecturerPermissions(),
            RoleEnum::HEAD_OF_DEPARTMENT->name() => PermissionHelper::hodPermissions(),
            RoleEnum::HEAD_OF_DIVISION->name() => PermissionHelper::headOfDivisionPermissions(),
            RoleEnum::VICE_PRINCIPAL->name() => PermissionHelper::vpAcademicsPermissions(),
            RoleEnum::VICE_PRINCIPAL_ADMIN->name() => PermissionHelper::vpAdminPermissions(),
            RoleEnum::PRINCIPAL->name() => PermissionHelper::principalPermissions(),
            RoleEnum::DEAN->name() => PermissionHelper::deanPermissions(),
            RoleEnum::WARDEN->name() => PermissionHelper::wardenPermissions(),
            RoleEnum::IT_SUPPORT_TECHNICIAN->name() => PermissionHelper::itSupportTechnicianPermissions(),
            default => null,
        };
    }
}
