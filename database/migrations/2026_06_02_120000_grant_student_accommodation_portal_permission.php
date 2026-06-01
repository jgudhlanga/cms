<?php

use App\Enums\Acl\RoleEnum;
use App\Models\Acl\Permission;
use App\Models\Acl\Role;
use App\Support\Acl\PermissionRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    public function up(): void
    {
        $permissionName = 'manageOwnStudentAccommodationDetails:students';

        if (! PermissionRegistry::exists($permissionName)) {
            return;
        }

        $permission = Permission::query()->firstOrCreate(['name' => $permissionName]);

        $studentRole = Role::query()->where('name', RoleEnum::STUDENT->name())->first();

        if ($studentRole && ! $studentRole->hasPermissionTo($permission)) {
            $studentRole->givePermissionTo($permission);
        }

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        // Permissions are managed via seeders; no rollback required.
    }
};
