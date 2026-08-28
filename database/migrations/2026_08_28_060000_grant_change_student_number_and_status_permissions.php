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
            'change-student-number:students',
            'change-student-status:students',
        ];

        $role = Role::query()->where('name', RoleEnum::SUPER_USER->name())->first();

        if (! $role instanceof Role) {
            return;
        }

        foreach ($permissionNames as $permissionName) {
            if (! PermissionRegistry::exists($permissionName)) {
                continue;
            }

            PermissionHelper::ensurePermissionExists($permissionName);

            if (! $role->hasPermissionTo($permissionName)) {
                $role->givePermissionTo($permissionName);
            }
        }

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        // Permissions are managed via seeders; no rollback required.
    }
};
