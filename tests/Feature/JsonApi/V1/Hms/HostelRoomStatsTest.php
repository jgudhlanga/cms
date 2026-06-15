<?php

use App\Enums\Shared\TenantEnum;
use App\Models\HMS\Hostel;
use App\Models\HMS\HostelRoom;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Laravel\Sanctum\Sanctum;

test('json api hostel rooms stats action returns aggregated meta', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $tenantId = TenantEnum::HARARE_POLY->id();

    $hostel = Hostel::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Stats Hostel',
        'location' => 'North',
        'floor_count' => 1,
        'rooms_count' => 2,
        'capacity' => 10,
        'status' => 'active',
        'type' => 'male',
    ]);

    HostelRoom::query()->create([
        'tenant_id' => $tenantId,
        'hostel_id' => $hostel->id,
        'name' => 'A-01',
        'room_type' => 'single',
        'capacity' => 2,
        'max_occupancy' => 2,
        'current_occupancy' => 0,
        'status' => 'vacant',
        'floor_number' => 0,
    ]);

    HostelRoom::query()->create([
        'tenant_id' => $tenantId,
        'hostel_id' => $hostel->id,
        'name' => 'B-02',
        'room_type' => 'double',
        'capacity' => 4,
        'max_occupancy' => 3,
        'current_occupancy' => 0,
        'status' => 'occupied',
        'floor_number' => 1,
    ]);

    $response = $this
        ->jsonApi('hostel-rooms')
        ->get(route('v1.json.hostel-rooms.stats'));

    $response->assertSuccessful()
        ->assertHeader('Content-Type', 'application/vnd.api+json')
        ->assertJsonPath('meta.totalRooms', 2)
        ->assertJsonPath('meta.totalCapacity', 6)
        ->assertJsonPath('meta.totalMaxOccupancy', 5)
        ->assertJsonPath('meta.vacantCount', 1);
});

test('json api hostel rooms stats action requires authentication', function () {
    $this
        ->jsonApi('hostel-rooms')
        ->get(route('v1.json.hostel-rooms.stats'))
        ->assertUnauthorized();
});
