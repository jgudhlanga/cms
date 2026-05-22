<?php

use App\Enums\HMS\HostelAllocationStatusEnum;
use App\Enums\HMS\HostelAllocationTypeEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\HMS\Hostel;
use App\Models\HMS\HostelRoom;
use App\Models\HMS\HostelRoomAllocation;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Laravel\Sanctum\Sanctum;

function createHostelRoomForAllocationIndexTest(array $overrides = []): HostelRoom
{
    $tenantId = TenantEnum::HARARE_POLY->id();

    $hostel = Hostel::query()->create(array_merge([
        'tenant_id' => $tenantId,
        'name' => 'Index Hostel '.uniqid(),
        'location' => 'North',
        'floor_count' => 1,
        'rooms_count' => 1,
        'capacity' => 10,
        'status' => 'active',
        'type' => 'male',
    ], $overrides['hostel'] ?? []));

    return HostelRoom::query()->create(array_merge([
        'tenant_id' => $tenantId,
        'hostel_id' => $hostel->id,
        'name' => 'I-0-01',
        'room_type' => 'double',
        'capacity' => 2,
        'max_occupancy' => 2,
        'current_occupancy' => 0,
        'status' => 'vacant',
        'floor_number' => 0,
    ], $overrides['room'] ?? []));
}

function createStudentForAllocationIndexTest(): Student
{
    $tenantId = TenantEnum::HARARE_POLY->id();
    $title = Title::query()->firstOrCreate(['name' => 'Mr Idx']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male Idx']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single Idx']);
    $idType = IdType::query()->firstOrCreate(['name' => 'National ID Idx']);

    $user = User::factory()->create([
        'tenant_id' => $tenantId,
        'first_name' => 'Idx',
        'last_name' => 'Student',
    ]);

    return Student::query()->create([
        'tenant_id' => $tenantId,
        'user_id' => $user->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'id_type_id' => $idType->id,
        'id_number' => '63-'.str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT).'IDX',
        'date_of_birth' => '2002-01-01',
    ]);
}

test('hostel allocations index returns paginated allocation rows', function () {
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

    $response = $this->getJson(route('v1.hms.hostel-allocations'));

    $response->assertSuccessful()
        ->assertJsonPath('data.0.type', 'hostel_room_allocation')
        ->assertJsonPath('data.0.id', $allocation->id)
        ->assertJsonPath('data.0.attributes.studentNumber', 'S9001')
        ->assertJsonPath('data.0.attributes.roomName', $room->name)
        ->assertJsonPath('data.0.attributes.allocationType', 'direct')
        ->assertJsonPath('data.0.attributes.status', 'active');
});

test('hostel allocations index excludes pending by default', function () {
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

    $response = $this->getJson(route('v1.hms.hostel-allocations'));

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.attributes.status', 'active');
});

test('hostel allocations index orders active before checked-out', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $room = createHostelRoomForAllocationIndexTest();
    $checkedOutStudent = createStudentForAllocationIndexTest();
    $activeStudent = createStudentForAllocationIndexTest();

    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $checkedOutStudent->id,
        'status' => HostelAllocationStatusEnum::CHECKED_OUT,
    ]);

    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $activeStudent->id,
        'status' => HostelAllocationStatusEnum::ACTIVE,
    ]);

    $response = $this->getJson(route('v1.hms.hostel-allocations'));

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.attributes.status', 'active')
        ->assertJsonPath('data.1.attributes.status', 'checked-out');
});

test('hostel allocations index includes legacy closed rows without status filter', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $room = createHostelRoomForAllocationIndexTest();
    $closedStudent = createStudentForAllocationIndexTest();

    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $closedStudent->id,
        'status' => HostelAllocationStatusEnum::CLOSED,
    ]);

    $response = $this->getJson(route('v1.hms.hostel-allocations'));

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.attributes.status', 'closed');
});

test('hostel allocations index filters by status', function () {
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

    $response = $this->getJson(route('v1.hms.hostel-allocations', ['status' => 'checked-out']));

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.attributes.status', 'checked-out');
});

test('hostel allocations index filters by student search', function () {
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

    $response = $this->getJson(route('v1.hms.hostel-allocations', ['search' => 'UNIQUE-HMS-42']));

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.attributes.studentNumber', 'UNIQUE-HMS-42');
});
