<?php

namespace Database\Seeders\Rbac;

use App\Helpers\PermissionHelper;
use App\Models\Rbac\Module;
use App\Models\Rbac\Permission;
use App\Support\Rbac\PermissionRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

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
                PermissionHelper::ensurePermissionExists($permission);
                $permissionModel = Permission::query()
                    ->where('name', $permission)
                    ->where('guard_name', 'web')
                    ->first();

                if ($permissionModel !== null && $module !== null && $permissionModel->module_id !== $module->id) {
                    $permissionModel->update(['module_id' => $module->id]);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
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
