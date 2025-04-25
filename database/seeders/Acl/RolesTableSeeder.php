<?php

namespace Database\Seeders\Acl;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
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
					$this->assignSuperAdminstratorPermissions($role);
				}
			}
		}
	}

	private function assignSuperAdminstratorPermissions($role): void {
	 	$permissions = collect(PermissionEnum::cases())
				->reject(fn($case) => $case->value === PermissionEnum::MANAGE_OWN_TENANT_DATA->value)
				->mapWithKeys(fn($case) => [$case->value => $case->value]);
		$role->syncPermissions(array_values($permissions->toArray()));
	}
}
