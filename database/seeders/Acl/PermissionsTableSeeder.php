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
        $accommodationsPermissions = [
            'viewAny:accommodations',
            'view:accommodations',
            'create:accommodations',
            'update:accommodations',
            'delete:accommodations',
            'restore:accommodations',
            'forceDelete:accommodations',
            'import:accommodations',
            'export:accommodations',
            'crud-settings:accommodations',
            'viewAuditTrail:accommodations',
        ];

        Permission::whereIn('name', $accommodationsPermissions)->forceDelete();

        foreach (PermissionRegistry::grouped() as $moduleKey => $permissionRows) {
            $module = Module::where('title', PermissionRegistry::moduleTitleForGroupKey($moduleKey))->withTrashed()->first();

            foreach ($permissionRows as $permission) {
                $exist = Permission::where('name', $permission)->withTrashed()->first();

                if (! $exist instanceof Permission) {
                    Permission::create(['name' => $permission, 'module_id' => $module?->id]);
                }
            }
        }
    }
}
