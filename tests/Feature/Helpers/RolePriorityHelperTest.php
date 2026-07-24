<?php

use App\Enums\Rbac\RoleEnum;
use App\Helpers\RolePriorityHelper;
use App\Models\Rbac\Role;
use App\Models\Users\User;

beforeEach(function () {
    seedDashboardTestRoles();
});

test('resolvePrimaryRoleName returns highest priority role', function () {
    $user = User::factory()->create();

    $lecturerRole = Role::query()->where('name', RoleEnum::LECTURER->name())->firstOrFail();
    $principalRole = Role::query()->where('name', RoleEnum::PRINCIPAL->name())->firstOrFail();

    $user->assignRole($lecturerRole);
    $user->assignRole($principalRole);

    expect(RolePriorityHelper::resolvePrimaryRoleName($user->fresh()))->toBe('Principal');
});

test('resolvePrimaryRoleName falls back to first role when no priority match', function () {
    $user = User::factory()->create();
    $customRole = Role::query()->create([
        'name' => 'Custom Role',
        'guard_name' => 'web',
    ]);

    $user->assignRole($customRole);

    expect(RolePriorityHelper::resolvePrimaryRoleName($user->fresh()))->toBe('Custom Role');
});

test('resolvePrimaryRoleName falls back to User when no roles assigned', function () {
    $user = User::factory()->create();

    expect(RolePriorityHelper::resolvePrimaryRoleName($user))->toBe('User');
});
