<?php

namespace Database\Seeders\Acl;

use App\Enums\Shared\PermissionEnum;
use App\Enums\Shared\RoleEnum;
use App\Models\Acl\Role;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
	public function run(): void
	{
		foreach (RoleEnum::cases() as $row) {
			$exist = Role::where('name', $row->value)->first();
			if (!$exist instanceof Role) {
				$role = Role::create(['name' => $row->value]);
				if ($role->name == RoleEnum::SUPER_ADMINISTRATOR->value) {
					$this->assignSuperAdministratorPermissions($role);
				}
                if ($role->name == RoleEnum::STUDENT->value) {
                    $role->givePermissionTo(PermissionEnum::MANAGE_OWN_STUDENT_DATA->value);
                }
			}
		}
	}

	private function assignSuperAdministratorPermissions($role): void {
	 	$permissions = collect(PermissionEnum::cases())
				->reject(fn($case) => $case->value === PermissionEnum::MANAGE_OWN_TENANT_DATA->value || $case->value === PermissionEnum::MANAGE_OWN_STUDENT_DATA->value)
				->mapWithKeys(fn($case) => [$case->value => $case->value]);
		$role->syncPermissions(array_values($permissions->toArray()));
	}
}
