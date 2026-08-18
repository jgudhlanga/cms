<?php

declare(strict_types=1);

use App\Enums\Shared\ModuleEnum;
use App\Models\Rbac\Module;
use App\Services\Rbac\RbacModuleStateService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Module::query()->firstOrCreate(
            ['title' => ModuleEnum::GALLERY->value],
            ['status' => true],
        );

        app(RbacModuleStateService::class)->clearCache();
    }

    public function down(): void
    {
        Module::query()
            ->where('title', ModuleEnum::GALLERY->value)
            ->forceDelete();

        app(RbacModuleStateService::class)->clearCache();
    }
};
