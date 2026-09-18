<?php

declare(strict_types=1);

namespace App\Policies\Console;

use App\Enums\Shared\ModuleEnum;
use App\Models\Users\User;
use App\Services\Rbac\RbacModuleStateService;

class ConsolePolicy
{
    public function __construct(
        private readonly RbacModuleStateService $moduleState,
    ) {}

    public function viewConsole(User $user): bool
    {
        return $this->moduleEnabled()
            && (
                $user->can('view:console')
                || $user->can('run:console-commands')
            );
    }

    public function runConsoleCommands(User $user): bool
    {
        return $this->moduleEnabled() && $user->can('run:console-commands');
    }

    public function runDestructiveConsoleCommands(User $user): bool
    {
        return $this->moduleEnabled()
            && $user->can('run:console-commands')
            && $user->can('run:destructive-console-commands');
    }

    private function moduleEnabled(): bool
    {
        return $this->moduleState->isEnabled(ModuleEnum::CONSOLE->slug());
    }
}
