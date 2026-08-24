<?php

declare(strict_types=1);

use App\Enums\Rbac\RoleEnum;
use App\Helpers\PermissionHelper;
use App\Models\Rbac\Permission;
use App\Models\Rbac\Role;
use App\Support\Rbac\PermissionRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacyName = 'manage:enrolments';
        $permissionName = 'manage:online-application-catalogue';

        if (! PermissionRegistry::exists($permissionName)) {
            return;
        }

        $legacy = Permission::query()->where('name', $legacyName)->withTrashed()->first();
        $target = Permission::query()->where('name', $permissionName)->withTrashed()->first();

        if ($legacy instanceof Permission && ! $target instanceof Permission) {
            $legacy->update(['name' => $permissionName]);
            if ($legacy->trashed()) {
                $legacy->restore();
            }
            $target = $legacy->fresh();
        } elseif ($legacy instanceof Permission && $target instanceof Permission) {
            DB::table(config('permission.table_names.role_has_permissions'))
                ->where('permission_id', $legacy->id)
                ->update(['permission_id' => $target->id]);
            DB::table(config('permission.table_names.model_has_permissions'))
                ->where('permission_id', $legacy->id)
                ->update(['permission_id' => $target->id]);
            $legacy->forceDelete();
            if ($target->trashed()) {
                $target->restore();
            }
        }

        PermissionHelper::ensurePermissionExists($permissionName);
        $permission = Permission::query()->where('name', $permissionName)->first();

        if ($permission === null) {
            return;
        }

        $rolesWithLegacyGrant = Role::query()
            ->whereHas('permissions', fn ($q) => $q->where('name', $permissionName))
            ->get();

        foreach ($rolesWithLegacyGrant as $role) {
            if ($role->name !== RoleEnum::SUPER_USER->name()) {
                $role->revokePermissionTo($permissionName);
            }
        }

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
