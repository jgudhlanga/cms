<?php

declare(strict_types=1);

use App\Enums\Shared\ModuleEnum;
use App\Helpers\PermissionHelper;
use App\Models\Rbac\Module;
use App\Models\Rbac\Permission;
use App\Services\Rbac\RbacModuleStateService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $module = Module::query()->firstOrCreate(
            ['title' => ModuleEnum::STUDENT_IDS->value],
            ['status' => true],
        );

        Permission::query()
            ->whereIn('name', PermissionHelper::idCardAdminPermissions())
            ->update(['module_id' => $module->id]);

        app(RbacModuleStateService::class)->clearCache();
    }

    public function down(): void
    {
        $studentIdsModule = Module::query()
            ->where('title', ModuleEnum::STUDENT_IDS->value)
            ->first();
        $studentsModule = Module::query()
            ->where('title', ModuleEnum::STUDENTS->value)
            ->first();

        if ($studentIdsModule instanceof Module && $studentsModule instanceof Module) {
            Permission::query()
                ->whereIn('name', PermissionHelper::idCardAdminPermissions())
                ->where('module_id', $studentIdsModule->id)
                ->update(['module_id' => $studentsModule->id]);
        }

        $studentIdsModule?->forceDelete();

        app(RbacModuleStateService::class)->clearCache();
    }
};
