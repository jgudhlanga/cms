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
        $permissionName = 'manage:online-application-catalogue';

        if (! PermissionRegistry::exists($permissionName)) {
            // Legacy name used when this migration first ran before the rename.
            $permissionName = 'manage:enrolments';

            if (! PermissionRegistry::exists($permissionName)) {
                return;
            }
        }

        PermissionHelper::ensurePermissionExists($permissionName);

        $superUser = Role::query()->where('name', RoleEnum::SUPER_USER->name())->first();

        if ($superUser !== null && ! $superUser->hasPermissionTo($permissionName)) {
            $superUser->givePermissionTo($permissionName);
        }

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        // Permissions are managed via seeders; no rollback required.
    }
};
