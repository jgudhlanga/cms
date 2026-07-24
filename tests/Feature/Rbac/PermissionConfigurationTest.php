<?php

use App\Models\Rbac\Permission;
use App\Support\Rbac\PermissionRegistry;

test('configured permissions are unique', function () {
    $values = PermissionRegistry::allValues();

    expect($values)
        ->toBeArray()
        ->and($values)
        ->toHaveCount(count(array_unique($values)));
});

test('permissions seeder persists all configured permissions', function () {
    $configuredValues = PermissionRegistry::allValues();
    $persistedValues = Permission::query()->pluck('name')->all();

    sort($configuredValues);
    sort($persistedValues);

    expect($persistedValues)->toBe($configuredValues);
});
