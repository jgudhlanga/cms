<?php

namespace Database\Seeders\Acl;

use App\Models\Acl\Module;
use App\Models\Acl\Permission;
use App\Support\Acl\PermissionRegistry;
use Illuminate\Database\Seeder;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (PermissionRegistry::grouped() as $moduleKey => $permissionRows) {
            $module = Module::where('title', $moduleKey)->withTrashed()->first();

            foreach ($permissionRows as $permission) {
                $exist = Permission::where('name', $permission)->withTrashed()->first();

                if (! $exist instanceof Permission) {
                    Permission::create(['name' => $permission, 'module_id' => $module?->id]);
                }
            }
        }
    }
}
