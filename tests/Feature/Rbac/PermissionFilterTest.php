<?php

use App\Http\Filters\Rbac\PermissionFilter;
use App\Models\Rbac\Permission;
use App\Models\Rbac\Role;
use Illuminate\Http\Request;

test('permission filter does not constrain results when role is missing', function () {
    $permissionWithoutRole = Permission::factory()->create([
        'name' => 'permission-filter-no-role-'.uniqid(),
        'guard_name' => 'web',
    ]);

    $permissionWithRole = Permission::factory()->create([
        'name' => 'permission-filter-with-role-'.uniqid(),
        'guard_name' => 'web',
    ]);

    $role = Role::factory()->create([
        'name' => 'permission-filter-role-'.uniqid(),
        'guard_name' => 'web',
    ]);
    $role->givePermissionTo($permissionWithRole);

    $request = Request::create('/permissions', 'GET');
    app()->instance('request', $request);

    $filters = new PermissionFilter($request);

    $filteredIds = Permission::query()
        ->whereIn('id', [$permissionWithoutRole->id, $permissionWithRole->id])
        ->filter($filters)
        ->pluck('id')
        ->all();

    expect($filteredIds)
        ->toContain($permissionWithoutRole->id)
        ->toContain($permissionWithRole->id);
});

test('permission filter constrains results when role is provided', function () {
    $role = Role::factory()->create([
        'name' => 'permission-filter-role-param-'.uniqid(),
        'guard_name' => 'web',
    ]);

    $matchingPermission = Permission::factory()->create([
        'name' => 'permission-filter-matching-'.uniqid(),
        'guard_name' => 'web',
    ]);

    $nonMatchingPermission = Permission::factory()->create([
        'name' => 'permission-filter-non-matching-'.uniqid(),
        'guard_name' => 'web',
    ]);

    $role->givePermissionTo($matchingPermission);

    $request = Request::create('/permissions', 'GET', ['role' => $role->name]);
    app()->instance('request', $request);

    $filters = new PermissionFilter($request);

    $filteredIds = Permission::query()
        ->whereIn('id', [$matchingPermission->id, $nonMatchingPermission->id])
        ->filter($filters)
        ->pluck('id')
        ->all();

    expect($filteredIds)
        ->toContain($matchingPermission->id)
        ->not->toContain($nonMatchingPermission->id);
});
