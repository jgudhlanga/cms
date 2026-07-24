<?php

use App\Models\Rbac\Permission;
use App\Models\Users\User;

test('guests are redirected when visiting finance pages', function () {
    $this->get(route('finance.index'))->assertRedirect('/login');
    $this->get(route('finance.reconciliation'))->assertRedirect('/login');
});

test('authenticated users without finance permissions cannot visit finance pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('finance.index'))->assertForbidden();
    $this->actingAs($user)->get(route('finance.reconciliation'))->assertForbidden();
});

test('authenticated users with finance permissions can visit finance pages', function () {
    $user = User::factory()->create();

    Permission::findOrCreate('view:finances', 'web');

    $user->givePermissionTo('view:finances');

    $this->actingAs($user)->get(route('finance.index'))->assertSuccessful();
    $this->actingAs($user)->get(route('finance.reconciliation'))->assertSuccessful();
});
