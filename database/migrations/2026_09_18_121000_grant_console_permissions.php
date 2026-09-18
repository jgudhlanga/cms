<?php

declare(strict_types=1);

use App\Enums\Rbac\RoleEnum;
use App\Enums\Shared\ModuleEnum;
use App\Helpers\PermissionHelper;
use App\Models\Rbac\Module;
use App\Models\Rbac\Role;
use App\Support\Rbac\PermissionRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    public function up(): void
    {
        if (! Module::query()->where('title', ModuleEnum::CONSOLE->value)->exists()) {
            Module::query()->create(['title' => ModuleEnum::CONSOLE->value]);
        }

        $permissionNames = array_values(array_filter(
            [
                'view:console',
                'run:console-commands',
                'run:destructive-console-commands',
            ],
            static fn (string $permissionName): bool => PermissionRegistry::exists($permissionName),
        ));

        foreach ($permissionNames as $permissionName) {
            PermissionHelper::ensurePermissionExists($permissionName);
        }

        $role = Role::query()->where('name', RoleEnum::SUPER_USER->name())->first();

        if ($role instanceof Role) {
            foreach ($permissionNames as $permissionName) {
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
