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
        $permissionName = 'confirm-study-position:students';

        if (! PermissionRegistry::exists($permissionName)) {
            return;
        }

        PermissionHelper::ensurePermissionExists($permissionName);

        // The role packs only apply when the roles seeder runs, so existing roles are granted here.
        $roleNames = [
            RoleEnum::SUPER_USER->name(),
            RoleEnum::REGISTRAR->name(),
            RoleEnum::REGISTRY_OFFICER->name(),
            RoleEnum::HEAD_OF_DEPARTMENT->name(),
            RoleEnum::HEAD_OF_DIVISION->name(),
        ];

        Role::query()
            ->whereIn('name', $roleNames)
            ->get()
            ->each(function (Role $role) use ($permissionName): void {
                if (! $role->hasPermissionTo($permissionName)) {
                    $role->givePermissionTo($permissionName);
                }
            });

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        // Permissions are managed via seeders; no rollback required.
    }
};
