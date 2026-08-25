<?php

declare(strict_types=1);

use App\Enums\Rbac\RoleEnum;
use App\Helpers\PermissionHelper;
use App\Models\Rbac\Role;
use App\Support\Rbac\PermissionRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    public function up(): void
    {
        $permissionNames = [
            'view:missing-marks-report',
            'export:missing-marks-report',
            'escalate:missing-marks',
            'remind:missing-marks',
        ];

        foreach ($permissionNames as $permissionName) {
            if (! PermissionRegistry::exists($permissionName)) {
                continue;
            }

            PermissionHelper::ensurePermissionExists($permissionName);
        }

        $roleNames = [
            RoleEnum::SUPER_USER->name(),
            RoleEnum::PRINCIPAL->name(),
            RoleEnum::VICE_PRINCIPAL->name(),
        ];

        $roles = Role::query()->whereIn('name', $roleNames)->get();

        foreach ($roles as $role) {
            foreach ($permissionNames as $permissionName) {
                if (! PermissionRegistry::exists($permissionName)) {
                    continue;
                }

                if (! $role->hasPermissionTo($permissionName)) {
                    $role->givePermissionTo($permissionName);
                }
            }
        }

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        // Permissions are managed via seeders; no rollback required.
    }
};
