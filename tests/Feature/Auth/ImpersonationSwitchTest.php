<?php

use App\Models\Users\User;

test('authorized user can impersonate another user', function () {
    $impersonator = User::factory()->create();
    $targetUser = User::factory()->create();

    $impersonator->givePermissionTo('root:manage');

    $response = $this
        ->actingAs($impersonator)
        ->get(route('impersonate', ['id' => $targetUser->id]));

    $response->assertRedirect(route('settings.profile'));
    $this->assertAuthenticatedAs($targetUser);
    expect(session()->has('impersonated_by'))->toBeTrue();
});

test('authorized user can switch impersonation without 403', function () {
    $impersonator = User::factory()->create();
    $firstTarget = User::factory()->create();
    $secondTarget = User::factory()->create();

    $impersonator->givePermissionTo('root:manage');

    $this
        ->actingAs($impersonator)
        ->get(route('impersonate', ['id' => $firstTarget->id]))
        ->assertRedirect(route('settings.profile'));

    $this->assertAuthenticatedAs($firstTarget);

    $response = $this->get(route('impersonate', ['id' => $secondTarget->id]));

    $response->assertRedirect(route('settings.profile'));
    $this->assertAuthenticatedAs($secondTarget);
});

test('users cannot impersonate themselves', function () {
    $impersonator = User::factory()->create();
    $impersonator->givePermissionTo('root:manage');

    $this
        ->actingAs($impersonator)
        ->get(route('impersonate', ['id' => $impersonator->id]))
        ->assertForbidden();
});
