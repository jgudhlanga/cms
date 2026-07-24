<?php

use App\DTO\Rbac\RoleDto;
use App\DTO\Users\UpdateUserDto;
use App\Enums\Rbac\RoleEnum;
use App\Models\Rbac\Permission;
use App\Models\Rbac\Role;
use App\Models\Users\User;
use App\Repositories\Rbac\RoleRepository;
use App\Repositories\Users\UserRepository;
use App\Services\Rbac\UserPermissionMapService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Cache::flush();
});

it('caches permission map across repeated forUser calls', function (): void {
    $permission = Permission::findOrCreate('view:users', 'web');
    $role = Role::query()->firstOrCreate(
        ['name' => 'Perf Cache Role '.Str::random(6)],
        ['slug' => 'perf-cache-role-'.Str::random(6), 'guard_name' => 'web']
    );
    $role->syncPermissions([$permission->name]);

    $user = User::factory()->create();
    $user->assignRole($role);

    $service = app(UserPermissionMapService::class);

    $first = $service->forUser($user->fresh());
    expect($first->has('view:users'))->toBeTrue();

    // Corrupt the underlying role permissions — cached map must still return the old value.
    $role->syncPermissions([]);

    $second = $service->forUser($user->fresh());
    expect($second->has('view:users'))->toBeTrue();
});

it('flushes all permission maps when role permissions are updated via repository', function (): void {
    $viewUsers = Permission::findOrCreate('view:users', 'web');
    $viewStudents = Permission::findOrCreate('view:students', 'web');

    $role = Role::query()->create([
        'name' => 'Perf Flush Role '.Str::random(6),
        'slug' => 'perf-flush-role-'.Str::random(6),
        'guard_name' => 'web',
    ]);
    $role->syncPermissions([$viewUsers->name]);

    $user = User::factory()->create();
    $user->assignRole($role);

    $service = app(UserPermissionMapService::class);
    expect($service->forUser($user->fresh())->has('view:users'))->toBeTrue()
        ->and($service->forUser($user->fresh())->has('view:students'))->toBeFalse();

    app(RoleRepository::class)->update($role, new RoleDto(
        name: $role->name,
        description: $role->description,
        permissions: [$viewStudents->name],
    ));

    $map = $service->forUser($user->fresh());
    expect($map->has('view:students'))->toBeTrue()
        ->and($map->has('view:users'))->toBeFalse();
});

it('forgets permission map when user roles are synced via user repository', function (): void {
    $permission = Permission::findOrCreate('view:users', 'web');
    $roleA = Role::query()->create([
        'name' => 'Perf User Role A '.Str::random(6),
        'slug' => 'perf-user-role-a-'.Str::random(6),
        'guard_name' => 'web',
    ]);
    $roleA->syncPermissions([$permission->name]);

    $roleB = Role::query()->create([
        'name' => 'Perf User Role B '.Str::random(6),
        'slug' => 'perf-user-role-b-'.Str::random(6),
        'guard_name' => 'web',
    ]);

    $user = User::factory()->create();
    $user->assignRole($roleA);

    $service = app(UserPermissionMapService::class);
    expect($service->forUser($user->fresh())->has('view:users'))->toBeTrue();

    app(UserRepository::class)->update($user, new UpdateUserDto(
        first_name: $user->first_name,
        middle_name: $user->middle_name,
        last_name: $user->last_name,
        email: $user->email,
        phone_number: $user->phone_number,
        role_ids: [$roleB->name],
    ));

    expect($service->forUser($user->fresh())->has('view:users'))->toBeFalse();
});

it('excludes finance permissions for super users in cached map', function (): void {
    $superUserRoleName = RoleEnum::SUPER_USER->name();
    Role::query()->firstOrCreate(
        ['name' => $superUserRoleName],
        ['slug' => Str::slug($superUserRoleName), 'guard_name' => 'web']
    );

    $user = User::factory()->create();
    $user->assignRole($superUserRoleName);

    $map = app(UserPermissionMapService::class)->forUser($user);

    expect($map->has('view:users'))->toBeTrue()
        ->and($map->has('view:finances'))->toBeFalse()
        ->and($map->has('viewAny:finance-settings'))->toBeFalse();
});
