<?php

use App\Models\Preferences\UserPreference;
use App\Models\Shared\Status;
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

test('authenticated user can fetch their own caused activities', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $user->update(['phone_number' => '0779998877']);

    Sanctum::actingAs($user);

    $this->getJson(route('v1.me.activities'))
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);
});

test('activity endpoints filter by event description', function () {
    $tenant = Tenant::query()->firstOrFail();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $targetUser = User::factory()->create(['tenant_id' => $tenant->id]);
    $admin->givePermissionTo('view:users');

    $targetUser->update(['phone_number' => '0771112233']);

    Sanctum::actingAs($admin);

    $updated = $this->getJson(route('v1.users.activities', [
        'user' => $targetUser->id,
        'event' => 'updated',
    ]))->assertSuccessful();

    expect(collect($updated->json('data'))->every(
        fn (array $row) => ($row['attributes']['description'] ?? null) === 'updated'
    ))->toBeTrue();

    Sanctum::actingAs($admin);

    $admin->update(['phone_number' => '0774445566']);

    $meUpdated = $this->getJson(route('v1.me.activities', ['event' => 'updated']))
        ->assertSuccessful();

    expect(collect($meUpdated->json('data'))->every(
        fn (array $row) => ($row['attributes']['description'] ?? null) === 'updated'
    ))->toBeTrue();

    $meCreated = $this->getJson(route('v1.me.activities', ['event' => 'created']))
        ->assertSuccessful();

    expect(collect($meCreated->json('data'))->every(
        fn (array $row) => ($row['attributes']['description'] ?? null) === 'created'
    ))->toBeTrue();
});

test('user activity endpoint resolves belongs to foreign keys to labels', function () {
    $tenant = Tenant::factory()->create(['name' => 'Main Campus']);
    $alternateTenant = Tenant::factory()->create(['name' => 'Engineering School']);
    $status = Status::factory()->create(['title' => 'Suspended']);
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $targetUser = User::factory()->create(['tenant_id' => $tenant->id]);
    $admin->givePermissionTo('view:users');

    Sanctum::actingAs($admin);

    $targetUser->update([
        'tenant_id' => $alternateTenant->id,
        'status_id' => $status->id,
    ]);

    $this->getJson(route('v1.users.activities', [
        'user' => $targetUser->id,
        'event' => 'updated',
    ]))
        ->assertSuccessful()
        ->assertJsonPath('data.0.attributes.properties.tenant_id', $alternateTenant->name)
        ->assertJsonPath('data.0.attributes.properties.status_id', $status->title);
});

test('me activity endpoint keeps unresolved foreign keys as raw values', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'avatar_id' => null,
    ]);

    Sanctum::actingAs($user);

    $user->update(['avatar_id' => 99999]);

    $this->getJson(route('v1.me.activities'))
        ->assertSuccessful()
        ->assertJsonPath('data.0.attributes.properties.avatar_id', 99999);
});

test('guests cannot fetch me activities', function () {
    $this->getJson(route('v1.me.activities'))
        ->assertUnauthorized();
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
