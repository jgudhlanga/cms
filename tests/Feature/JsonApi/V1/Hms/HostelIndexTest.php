<?php

use App\Enums\HMS\HostelAllocationStatusEnum;
use App\Enums\HMS\HostelAllocationTypeEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\HMS\Hostel;
use App\Models\HMS\HostelRoom;
use App\Models\HMS\HostelRoomAllocation;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Laravel\Sanctum\Sanctum;

test('json api hostels index filters by type male', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $tenantId = TenantEnum::HARARE_POLY->id();

    Hostel::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Boys Block '.uniqid(),
        'location' => 'North',
        'floor_count' => 1,
        'rooms_count' => 1,
        'capacity' => 10,
        'status' => 'active',
        'type' => 'male',
    ]);

    Hostel::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Girls Block '.uniqid(),
        'location' => 'South',
        'floor_count' => 1,
        'rooms_count' => 1,
        'capacity' => 10,
        'status' => 'active',
        'type' => 'female',
    ]);

    $response = $this
        ->jsonApi('hostels')
        ->get(route('v1.json.hms.hostels.index', ['filter' => ['type' => 'male']]));

    $response->assertSuccessful();

    $types = collect($response->json('data'))->pluck('attributes.type')->unique()->values()->all();

    expect($types)->toBe(['male']);
});

test('json api hostels index returns occupied bed count from room occupancy', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $tenantId = TenantEnum::HARARE_POLY->id();

    $hostel = Hostel::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Stats Block '.uniqid(),
        'location' => 'North',
        'floor_count' => 1,
        'rooms_count' => 2,
        'capacity' => 4,
        'status' => 'active',
        'type' => 'male',
    ]);

    $room = HostelRoom::query()->create([
        'tenant_id' => $tenantId,
        'hostel_id' => $hostel->id,
        'name' => 'STAT-01',
        'room_type' => 'double',
        'capacity' => 2,
        'max_occupancy' => 2,
        'current_occupancy' => 0,
        'status' => 'vacant',
        'floor_number' => 0,
    ]);

    $student = createStudentForAllocationIndexTest();

    HostelRoomAllocation::query()->create([
        'tenant_id' => $tenantId,
        'hostel_room_id' => $room->id,
        'student_id' => $student->id,
        'type' => HostelAllocationTypeEnum::DIRECT,
        'status' => HostelAllocationStatusEnum::ACTIVE,
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]);

    $room->syncOccupancyFromAllocations();

    $response = $this
        ->jsonApi('hostels')
        ->get(route('v1.json.hms.hostels.index', ['filter' => ['search' => $hostel->name]]));

    $response->assertSuccessful()
        ->assertJsonPath('data.0.attributes.occupiedCount', 1)
        ->assertJsonPath('data.0.attributes.vacantCount', 0);
});

test('json api hostels index filters by type female case insensitively', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $tenantId = TenantEnum::HARARE_POLY->id();

    Hostel::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Girls Only '.uniqid(),
        'location' => 'East',
        'floor_count' => 1,
        'rooms_count' => 1,
        'capacity' => 10,
        'status' => 'active',
        'type' => 'female',
    ]);

    $response = $this
        ->jsonApi('hostels')
        ->get(route('v1.json.hms.hostels.index', ['filter' => ['type' => 'Female']]));

    $response->assertSuccessful();

    expect(collect($response->json('data'))->pluck('attributes.type')->unique()->values()->all())
        ->toBe(['female']);
});
