<?php

use App\Enums\Shared\TenantEnum;
use App\Models\HMS\Hostel;
use App\Models\HMS\HostelRoom;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Laravel\Sanctum\Sanctum;

test('json api hostel rooms index applies default name sort', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $tenantId = TenantEnum::HARARE_POLY->id();

    $hostel = Hostel::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Sort Hostel',
        'location' => 'South',
        'floor_count' => 1,
        'rooms_count' => 2,
        'capacity' => 10,
        'status' => 'active',
        'type' => 'male',
    ]);

    HostelRoom::query()->create([
        'tenant_id' => $tenantId,
        'hostel_id' => $hostel->id,
        'name' => 'Z-99',
        'room_type' => 'double',
        'capacity' => 2,
        'max_occupancy' => 2,
        'current_occupancy' => 0,
        'status' => 'vacant',
        'floor_number' => 1,
    ]);

    HostelRoom::query()->create([
        'tenant_id' => $tenantId,
        'hostel_id' => $hostel->id,
        'name' => 'A-01',
        'room_type' => 'single',
        'capacity' => 1,
        'max_occupancy' => 1,
        'current_occupancy' => 0,
        'status' => 'vacant',
        'floor_number' => 0,
    ]);

    $response = $this
        ->jsonApi('hostel-rooms')
        ->get(route('v1.json.hostel-rooms.index'));

    $response->assertSuccessful()
        ->assertJsonPath('data.0.attributes.name', 'A-01')
        ->assertJsonPath('data.1.attributes.name', 'Z-99');
});

test('json api hostel rooms index accepts explicit sort parameter', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $response = $this
        ->jsonApi('hostel-rooms')
        ->sort('-name')
        ->get(route('v1.json.hostel-rooms.index'));

    $response->assertSuccessful();
});

test('json api hostel rooms index rejects legacy flat page query', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $this
        ->jsonApi('hostel-rooms')
        ->get(route('v1.json.hostel-rooms.index', ['page' => 2]))
        ->assertStatus(400);
});

test('json api hostel rooms index accepts page number and size array params', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $this
        ->jsonApi('hostel-rooms')
        ->page(['number' => 1, 'size' => 10])
        ->get(route('v1.json.hostel-rooms.index'))
        ->assertSuccessful();
});
