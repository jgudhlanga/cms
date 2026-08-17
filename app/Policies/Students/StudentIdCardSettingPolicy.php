<?php

declare(strict_types=1);

namespace App\Policies\Students;

use App\Enums\Shared\ModuleEnum;
use App\Models\Students\StudentIdCardSetting;
use App\Models\Users\User;
use App\Services\Rbac\RbacModuleStateService;

class StudentIdCardSettingPolicy
{
    public function __construct(
        private readonly RbacModuleStateService $moduleState,
    ) {}

    public function view(User $user, ?StudentIdCardSetting $studentIdCardSetting = null): bool
    {
        return $this->moduleEnabled()
            && ($user->can('view:student-id-card-settings') || $user->can('update:student-id-card-settings'));
    }

    public function update(User $user, ?StudentIdCardSetting $studentIdCardSetting = null): bool
    {
        return $this->moduleEnabled() && $user->can('update:student-id-card-settings');
    }

    private function moduleEnabled(): bool
    {
        return $this->moduleState->isEnabled(ModuleEnum::STUDENT_IDS->slug());
    }
}
