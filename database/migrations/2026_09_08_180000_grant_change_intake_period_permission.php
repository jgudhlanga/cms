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
        $permissionName = 'change-intake-period:students';

        $role = Role::query()->where('name', RoleEnum::SUPER_USER->name())->first();

        if (! $role instanceof Role) {
            return;
        }

        if (! PermissionRegistry::exists($permissionName)) {
            return;
        }

        PermissionHelper::ensurePermissionExists($permissionName);

        if (! $role->hasPermissionTo($permissionName)) {
            $role->givePermissionTo($permissionName);
        }

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        // Permissions are managed via seeders; no rollback required.
    }
};
