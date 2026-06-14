<?php

use App\Models\Preferences\UserPreference;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Laravel\Sanctum\Sanctum;

test('authorized user can fetch paginated user activities', function () {
    $tenant = Tenant::query()->firstOrFail();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $targetUser = User::factory()->create(['tenant_id' => $tenant->id]);
    $admin->givePermissionTo('view:users');

    $targetUser->update(['phone_number' => '0771234567']);

    Sanctum::actingAs($admin);

    $this->getJson(route('v1.users.activities', ['user' => $targetUser->id]))
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'attributes' => [
                        'description',
                        'logName',
                        'createdAt',
                    ],
                ],
            ],
            'links',
            'meta',
        ]);
});

test('unauthorized user cannot fetch user activities', function () {
    $tenant = Tenant::query()->firstOrFail();
    $viewer = User::factory()->create(['tenant_id' => $tenant->id]);
    $targetUser = User::factory()->create(['tenant_id' => $tenant->id]);

    Sanctum::actingAs($viewer);

    $this->getJson(route('v1.users.activities', ['user' => $targetUser->id]))
        ->assertForbidden();
});

test('authorized user can update another users preferences', function () {
    $tenant = Tenant::query()->firstOrFail();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $targetUser = User::factory()->create(['tenant_id' => $tenant->id]);
    $admin->givePermissionTo('update:users');

    Sanctum::actingAs($admin);

    $this->putJson(route('v1.users.preferences.update', ['user' => $targetUser->id]), [
        'side_bar_state' => true,
        'locale' => 'en',
    ])
        ->assertSuccessful()
        ->assertJsonPath('attributes.sideBarState', true)
        ->assertJsonPath('attributes.locale', 'en');

    expect(UserPreference::query()->where('user_id', $targetUser->id)->value('side_bar_state'))->toBeTrue();
});

test('unauthorized user cannot update another users preferences', function () {
    $tenant = Tenant::query()->firstOrFail();
    $viewer = User::factory()->create(['tenant_id' => $tenant->id]);
    $targetUser = User::factory()->create(['tenant_id' => $tenant->id]);

    Sanctum::actingAs($viewer);

    $this->putJson(route('v1.users.preferences.update', ['user' => $targetUser->id]), [
        'side_bar_state' => true,
    ])->assertForbidden();
});
