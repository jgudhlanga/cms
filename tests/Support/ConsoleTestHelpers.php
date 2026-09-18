<?php

use App\Enums\Shared\ModuleEnum;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Rbac\Module;
use App\Models\Rbac\Permission;
use App\Models\Users\User;
use App\Services\Rbac\RbacModuleStateService;
use Illuminate\Http\Request;

/**
 * @param  list<string>  $permissions
 */
function consoleUser(array $permissions = ['view:console', 'run:console-commands']): User
{
    $user = User::factory()->create(['password' => 'password']);

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    return $user;
}

function disableConsoleModule(): void
{
    Module::query()
        ->where('slug', ModuleEnum::CONSOLE->slug())
        ->firstOrFail()
        ->update(['status' => false]);

    app(RbacModuleStateService::class)->clearCache();
}

/**
 * @param  list<string>  $only
 * @return array<string, string>
 */
function consoleInertiaHeaders(array $only = []): array
{
    $headers = [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(Request::create('/')),
    ];

    if ($only !== []) {
        $headers['X-Inertia-Partial-Component'] = 'console/Index';
        $headers['X-Inertia-Partial-Data'] = implode(',', $only);
    }

    return $headers;
}
