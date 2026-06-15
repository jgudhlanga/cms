<?php

use App\Enums\HMS\HostelAllocationStatusEnum;
use App\Enums\HMS\HostelAllocationTypeEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\HMS\HostelRoomAllocation;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Laravel\Sanctum\Sanctum;

test('json api hostel allocations index returns paginated allocation rows', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $room = createHostelRoomForAllocationIndexTest();
    $student = createStudentForAllocationIndexTest();

    $allocation = HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $student->id,
        'type' => HostelAllocationTypeEnum::DIRECT,
        'status' => HostelAllocationStatusEnum::ACTIVE,
    ]);

    $student->update(['student_number' => 'S9001']);

    $response = $this
        ->jsonApi('hostel-room-allocations')
        ->get(route('v1.json.hms.hostel-room-allocations.index'));

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', (string) $allocation->id)
        ->assertJsonPath('data.0.attributes.studentNumber', 'S9001')
        ->assertJsonPath('data.0.attributes.roomName', $room->name)
        ->assertJsonPath('data.0.attributes.allocationType', 'direct')
        ->assertJsonPath('data.0.attributes.status', 'active');
});

test('json api hostel allocations index excludes pending by default', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $room = createHostelRoomForAllocationIndexTest();
    $activeStudent = createStudentForAllocationIndexTest();
    $pendingStudent = createStudentForAllocationIndexTest();

    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $activeStudent->id,
        'status' => HostelAllocationStatusEnum::ACTIVE,
    ]);

    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $pendingStudent->id,
        'status' => HostelAllocationStatusEnum::PENDING,
    ]);

    $response = $this
        ->jsonApi('hostel-room-allocations')
        ->get(route('v1.json.hms.hostel-room-allocations.index'));

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.attributes.status', 'active');
});

test('json api hostel allocations index filters by status', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $room = createHostelRoomForAllocationIndexTest();
    $activeStudent = createStudentForAllocationIndexTest();
    $checkedOutStudent = createStudentForAllocationIndexTest();

    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $activeStudent->id,
        'status' => HostelAllocationStatusEnum::ACTIVE,
    ]);

    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $checkedOutStudent->id,
        'status' => HostelAllocationStatusEnum::CHECKED_OUT,
    ]);

    $response = $this
        ->jsonApi('hostel-room-allocations')
        ->filter(['status' => 'checked-out'])
        ->get(route('v1.json.hms.hostel-room-allocations.index'));

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.attributes.status', 'checked-out');
});

test('json api hostel allocations index filters by student search', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $room = createHostelRoomForAllocationIndexTest();
    $student = createStudentForAllocationIndexTest();
    $student->update(['student_number' => 'UNIQUE-HMS-42']);

    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $student->id,
    ]);

    createStudentForAllocationIndexTest();

    $response = $this
        ->jsonApi('hostel-room-allocations')
        ->filter(['search' => 'UNIQUE-HMS-42'])
        ->get(route('v1.json.hms.hostel-room-allocations.index'));

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.attributes.studentNumber', 'UNIQUE-HMS-42');
});

test('json api hostel allocations index requires authentication', function () {
    $this
        ->jsonApi('hostel-room-allocations')
        ->get(route('v1.json.hms.hostel-room-allocations.index'))
        ->assertUnauthorized();
});
