<?php

use App\Enums\Shared\StatusEnum;
use App\Models\Shared\Status;
use App\Models\Users\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

test('authenticated staff can view audit trail page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('users.audit-trail'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('users/AuditTrail'));
});

test('authenticated user can fetch own caused activities', function () {
    $user = User::factory()->create();
    $user->update(['phone_number' => '0771110001']);

    Sanctum::actingAs($user);

    $this->getJson(route('v1.users.caused-activities', ['user' => $user->id]))
        ->assertSuccessful()
        ->assertJsonStructure(['data', 'links', 'meta']);
});

test('caused activities include previous values for updates', function () {
    $user = User::factory()->create();

    activity()
        ->causedBy($user)
        ->performedOn($user)
        ->withProperties([
            'attributes' => ['phone_number' => '0771110001'],
            'old' => ['phone_number' => '0770000000'],
        ])
        ->log('updated');

    Sanctum::actingAs($user);

    $updated = collect($this->getJson(route('v1.users.caused-activities', ['user' => $user->id]))
        ->assertSuccessful()
        ->json('data'))
        ->first(fn (array $row): bool => ($row['attributes']['description'] ?? null) === 'updated');

    expect($updated)->not->toBeNull()
        ->and($updated['attributes']['properties']['phone_number'])->toBe('0771110001')
        ->and($updated['attributes']['oldProperties']['phone_number'])->toBe('0770000000');
});

test('caused activities resolve foreign keys to related labels', function () {
    $user = User::factory()->create();
    $active = Status::query()->find(StatusEnum::ACTIVE->id());
    $inactive = Status::query()->find(StatusEnum::INACTIVE->id());

    activity()
        ->causedBy($user)
        ->performedOn($user)
        ->withProperties([
            'attributes' => ['status_id' => $inactive->id],
            'old' => ['status_id' => $active->id],
        ])
        ->log('updated');

    Sanctum::actingAs($user);

    $updated = collect($this->getJson(route('v1.users.caused-activities', ['user' => $user->id]))
        ->assertSuccessful()
        ->json('data'))
        ->first(fn (array $row): bool => ($row['attributes']['description'] ?? null) === 'updated');

    expect($updated)->not->toBeNull()
        ->and($updated['attributes']['properties']['status_id'])->toBe($inactive->title)
        ->and($updated['attributes']['oldProperties']['status_id'])->toBe($active->title);
});

test('caused activities can be filtered to deleted events', function () {
    $user = User::factory()->create();

    activity()
        ->causedBy($user)
        ->performedOn($user)
        ->log('updated');

    activity()
        ->causedBy($user)
        ->performedOn($user)
        ->withProperties([
            'old' => ['phone_number' => '0770000000'],
        ])
        ->log('deleted');

    Sanctum::actingAs($user);

    $this->getJson(route('v1.users.caused-activities', [
        'user' => $user->id,
        'event' => 'deleted',
    ]))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.attributes.description', 'deleted');
});

test('non root user cannot fetch another users caused activities', function () {
    $actor = User::factory()->create();
    $target = User::factory()->create(['tenant_id' => $actor->tenant_id]);

    Sanctum::actingAs($actor);

    $this->getJson(route('v1.users.caused-activities', ['user' => $target->id]))
        ->assertForbidden();
});

test('root manage user can fetch another users caused activities', function () {
    $actor = User::factory()->create();
    Permission::findOrCreate('root:manage', 'web');
    $actor->givePermissionTo('root:manage');

    $target = User::factory()->create(['tenant_id' => $actor->tenant_id]);
    $target->update(['phone_number' => '0772220002']);

    Sanctum::actingAs($actor);

    $this->getJson(route('v1.users.caused-activities', ['user' => $target->id]))
        ->assertSuccessful();
});

test('activity lookup requires root manage', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->getJson(route('v1.users.activity-lookup', ['search' => 'a']))
        ->assertForbidden();
});

test('root manage user can lookup users for activity trail', function () {
    $actor = User::factory()->create();
    Permission::findOrCreate('root:manage', 'web');
    $actor->givePermissionTo('root:manage');

    $match = User::factory()->create([
        'tenant_id' => $actor->tenant_id,
        'first_name' => 'LookupUnique',
        'last_name' => 'Person',
        'email' => 'lookup-unique@example.com',
    ]);

    Sanctum::actingAs($actor);

    $this->getJson(route('v1.users.activity-lookup', ['search' => 'LookupUnique']))
        ->assertSuccessful()
        ->assertJsonFragment([
            'id' => $match->id,
            'name' => $match->full_name,
            'email' => $match->email,
        ]);
});
