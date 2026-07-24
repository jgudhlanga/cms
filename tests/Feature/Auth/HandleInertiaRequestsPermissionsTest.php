<?php

use App\Enums\Rbac\RoleEnum;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Rbac\Role;
use App\Models\Users\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

test('super user excludes all finance permissions from shared auth can map', function () {
    $superUserRoleName = RoleEnum::SUPER_USER->name();

    Role::query()->firstOrCreate(
        ['name' => $superUserRoleName],
        [
            'slug' => Str::slug($superUserRoleName),
            'guard_name' => 'web',
        ]
    );

    $user = User::factory()->create();
    $user->assignRole($superUserRoleName);

    $middleware = new HandleInertiaRequests;
    $reflection = new ReflectionMethod(HandleInertiaRequests::class, 'permissions');
    $reflection->setAccessible(true);

    /** @var Collection<string, bool> $permissions */
    $permissions = $reflection->invoke($middleware, $user);

    expect($permissions->has('view:users'))->toBeTrue()
        ->and($permissions->has('view:finances'))->toBeFalse()
        ->and($permissions->has('create:finances'))->toBeFalse()
        ->and($permissions->has('viewAny:finance-settings'))->toBeFalse()
        ->and($permissions->has('update:finance-settings'))->toBeFalse();
});
