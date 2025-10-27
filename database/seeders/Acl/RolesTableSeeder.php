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
        foreach (RoleEnum::cases() as $row) {
            $exist = Role::where('name', $row->name())->first();
            if (!$exist instanceof Role) {
                $role = Role::create(
                    [
                        'name' => $row->name(),
                        'role_group_id' => $this->getGroupId($row->group()),
                        'description' => $row->description()
                    ]);
                if ($role->name == RoleEnum::SUPER_USER->name()) {
                    $this->assignSuperUserPermissions($role);
                }
                if ($role->name == RoleEnum::STUDENT->name()) {
                    $role->givePermissionTo($this->portalPermissions());
                }
            }
        }
    }

    /**
     * @param $slug
     * @return mixed
     */
    private function getGroupId($slug): mixed
    {
        $roleGroup = RoleGroup::where('slug', $slug)->first();
        return $roleGroup->id ?? null;
    }

    private function assignSuperUserPermissions($role): void
    {
        $excludedPermissions = collect(array_merge(
            $this->portalPermissions(),
            [PermissionEnum::MANAGE_OWN_TENANT_DATA->value, PermissionEnum::VIEW_ONLY_OWN_DEPARTMENT->value]
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
            PermissionEnum::VIEW_NEXT_OF_KINS->value,
            PermissionEnum::CREATE_NEXT_OF_KINS->value,
            PermissionEnum::UPDATE_NEXT_OF_KINS->value,
            PermissionEnum::DELETE_NEXT_OF_KINS->value,
            PermissionEnum::FORCE_DELETE_NEXT_OF_KINS->value,
        ];
    }
}
