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
            'updateClosed:assessment-calendar',
            'viewAny:department-assessment-calendar',
            'view:department-assessment-calendar',
            'create:department-assessment-calendar',
            'update:department-assessment-calendar',
            'delete:department-assessment-calendar',
            'restore:department-assessment-calendar',
            'forceDelete:department-assessment-calendar',
            'viewAuditTrail:department-assessment-calendar',
            'captureForOthers:course-work',
            'request:course-work-extensions',
            'viewAny:course-work-extensions',
            'approve:course-work-extensions',
            'approveBeyondGlobal:course-work-extensions',
            'revoke:course-work-extensions',
            'assign:lecturer-in-charge',
            'view:course-work-progress',
            'submit:course-work-progress-reports',
            'acknowledge:course-work-progress-reports',
        ];

        $permissionNames = array_values(array_filter(
            $permissionNames,
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
