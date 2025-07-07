<?php

namespace Database\Seeders\Acl;

use App\Enums\Acl\PermissionEnum;
use App\Enums\Acl\RoleEnum;
use App\Models\Acl\Role;
use App\Models\Acl\RoleGroup;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RoleEnum::cases() as $role) {
            $exist = Role::where('name', $role->name())->first();
            if (!$exist instanceof Role) {
                $role = Role::create(
                    [
                        'name' => $role->name,
                        'role_group_id' => $this->getGroupId($role->group()),
                        'description' => $role->description()
                    ]);
                if ($role->name == RoleEnum::SUPER_ADMINISTRATOR->value) {
                    $this->assignSuperAdministratorPermissions($role);
                }
                if ($role->name == RoleEnum::STUDENT->value) {
                    $role->givePermissionTo($this->portalPermissions());
                }
            }
        }
    }

    private function getGroupId($slug)
    {
        $roleGroup = RoleGroup::where('slug', $slug)->first();
        return $roleGroup ?? null;
    }

    private function assignSuperAdministratorPermissions($role): void
    {
        $excludedPermissions = collect(array_merge(
            $this->portalPermissions(),
            [PermissionEnum::MANAGE_OWN_TENANT_DATA->value]
        ));
        $permissions = collect(PermissionEnum::cases())
            ->reject(fn($case) => $excludedPermissions->contains($case->value))
            ->mapWithKeys(fn($case) => [$case->value => $case->value]);
        $role->syncPermissions(array_values($permissions->toArray()));
    }

    private function portalPermissions(): array
    {
        return [
            PermissionEnum::VIEW_OWN_STUDENT_DASHBOARD->value,
            PermissionEnum::MANAGE_OWN_STUDENT_PERSONAL_DETAILS->value,
            PermissionEnum::MANAGE_OWN_STUDENT_PROGRAM_DETAILS->value,
            PermissionEnum::MANAGE_OWN_STUDENT_SPONSOR_DETAILS->value,
            PermissionEnum::MANAGE_OWN_STUDENT_CONTACT_DETAILS->value,
            PermissionEnum::MANAGE_OWN_STUDENT_FINANCIAL_DETAILS->value,
            PermissionEnum::MANAGE_OWN_STUDENT_ACADEMIC_DETAILS->value,
        ];
    }
}
