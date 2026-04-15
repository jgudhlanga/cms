<?php

use App\Models\Preferences\UserPreference;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Laravel\Sanctum\Sanctum;

test('authenticated user can fetch sidebar preference and gets default when missing', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    Sanctum::actingAs($user);

    $this->getJson(route('v1.preferences.index'))
        ->assertSuccessful()
        ->assertJsonPath('attributes.sideBarState', false);

    expect(UserPreference::query()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('authenticated user can store and update own sidebar preference', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    Sanctum::actingAs($user);

    $createResponse = $this->postJson(route('v1.preferences.store'), [
        'side_bar_state' => true,
    ]);

    $createResponse
        ->assertSuccessful()
        ->assertJsonPath('attributes.sideBarState', true);

    $preferenceId = $createResponse->json('id');

    $this->putJson(route('v1.preferences.update', ['preference' => $preferenceId]), [
        'side_bar_state' => false,
    ])
        ->assertSuccessful()
        ->assertJsonPath('attributes.sideBarState', false);
});

test('user cannot update another users preference', function () {
    $tenant = Tenant::query()->firstOrFail();
    $currentUser = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
    $otherUser = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);
    $otherPreference = UserPreference::factory()->create([
        'user_id' => $otherUser->id,
        'side_bar_state' => true,
    ]);

    Sanctum::actingAs($currentUser);

    $this->putJson(route('v1.preferences.update', ['preference' => $otherPreference->id]), [
        'side_bar_state' => false,
    ])->assertForbidden();
});

test('sidebar preference endpoints validate required boolean payload', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    Sanctum::actingAs($user);

    $this->postJson(route('v1.preferences.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['side_bar_state']);
});
