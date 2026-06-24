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

        $this->migrateLegacyPermissionNames();

        foreach (PermissionRegistry::grouped() as $moduleKey => $permissionRows) {
            $module = Module::where('title', PermissionRegistry::moduleTitleForGroupKey($moduleKey))->withTrashed()->first();

            foreach ($permissionRows as $permission) {
                $exist = Permission::where('name', $permission)->withTrashed()->first();

                if ($exist instanceof Permission) {
                    if ($exist->trashed()) {
                        $exist->restore();
                    }

                    if ($module !== null && $exist->module_id !== $module->id) {
                        $exist->update(['module_id' => $module->id]);
                    }

                    continue;
                }

                Permission::create(['name' => $permission, 'module_id' => $module?->id]);
            }
        }
    }

    private function migrateLegacyPermissionNames(): void
    {
        $actions = [
            'viewAny',
            'view',
            'create',
            'update',
            'delete',
            'restore',
            'forceDelete',
            'import',
            'export',
            'crud-settings',
            'viewAuditTrail',
        ];

        foreach ($actions as $action) {
            $this->renamePermissionIfNeeded("{$action}:student-programs", "{$action}:student-applications");
        }

        $this->renamePermissionIfNeeded(
            'manageOwnStudentProgramDetails:students',
            'manageOwnStudentApplicationDetails:students'
        );
    }

    private function renamePermissionIfNeeded(string $from, string $to): void
    {
        $legacyPermission = Permission::where('name', $from)->withTrashed()->first();

        if (! $legacyPermission instanceof Permission) {
            return;
        }

        $targetPermission = Permission::where('name', $to)->withTrashed()->first();

        if ($targetPermission instanceof Permission) {
            $legacyPermission->forceDelete();

            if ($targetPermission->trashed()) {
                $targetPermission->restore();
            }

            return;
        }

        $legacyPermission->update(['name' => $to]);

        if ($legacyPermission->trashed()) {
            $legacyPermission->restore();
        }
    }
}
