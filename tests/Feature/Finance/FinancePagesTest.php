<?php

use App\Models\Acl\Permission;
use App\Models\Users\User;

test('guests are redirected when visiting finance pages', function () {
    $this->get(route('finance.index'))->assertRedirect('/login');
    $this->get(route('finance.settings'))->assertRedirect('/login');
});

test('authenticated users without finance permissions cannot visit finance pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('finance.index'))->assertForbidden();
    $this->actingAs($user)->get(route('finance.settings'))->assertForbidden();
});

test('authenticated users with finance permissions can visit finance pages', function () {
    $user = User::factory()->create();

    Permission::findOrCreate('view:finances', 'web');
    Permission::findOrCreate('view:finance-settings', 'web');

    $user->givePermissionTo('view:finances');
    $user->givePermissionTo('view:finance-settings');

    $this->actingAs($user)->get(route('finance.index'))->assertSuccessful();
    $this->actingAs($user)->get(route('finance.settings'))->assertSuccessful();
});
