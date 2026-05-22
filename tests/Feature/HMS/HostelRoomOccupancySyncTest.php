<?php

use App\Enums\HMS\HostelAllocationStatusEnum;
use App\Enums\Shared\TenantEnum;
use App\Http\Resources\HMS\HostelRoomResource;
use App\Models\HMS\Hostel;
use App\Models\HMS\HostelRoom;
use App\Models\HMS\HostelRoomAllocation;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Users\User;
use Illuminate\Validation\ValidationException;

function createHostelRoomForOccupancyTest(array $overrides = []): HostelRoom
{
    $tenantId = TenantEnum::HARARE_POLY->id();

    $hostel = Hostel::query()->create(array_merge([
        'tenant_id' => $tenantId,
        'name' => 'Test Hostel '.uniqid(),
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
        'name' => 'T-0-01',
        'room_type' => 'double',
        'capacity' => 2,
        'max_occupancy' => 2,
        'current_occupancy' => 0,
        'status' => 'vacant',
        'floor_number' => 0,
    ], $overrides['room'] ?? []));
}

function createStudentForOccupancyTest(): Student
{
    $tenantId = TenantEnum::HARARE_POLY->id();
    $title = Title::query()->firstOrCreate(['name' => 'Mr Occ']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male Occ']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single Occ']);
    $idType = IdType::query()->firstOrCreate(['name' => 'National ID Occ']);

    $user = User::factory()->create([
        'tenant_id' => $tenantId,
        'first_name' => 'Occ',
        'last_name' => 'Student',
    ]);

    return Student::query()->create([
        'tenant_id' => $tenantId,
        'user_id' => $user->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'id_type_id' => $idType->id,
        'id_number' => '63-'.str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT).'OCC',
        'date_of_birth' => '2002-01-01',
    ]);
}

test('assigning one student sets occupancy to 1/2 and occupied status', function () {
    $room = createHostelRoomForOccupancyTest();
    $student = createStudentForOccupancyTest();

    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $student->id,
    ]);

    $room->refresh();

    expect($room->current_occupancy)->toBe(1)
        ->and($room->status)->toBe('occupied')
        ->and($room->occupancyLabel())->toBe('1/2');

    $resource = (new HostelRoomResource($room))->resolve();
    expect($resource['attributes']['occupancy'])->toBe('1/2');
});

test('assigning up to max occupancy sets full room', function () {
    $room = createHostelRoomForOccupancyTest();
    $studentOne = createStudentForOccupancyTest();
    $studentTwo = createStudentForOccupancyTest();

    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $studentOne->id,
    ]);

    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $studentTwo->id,
    ]);

    $room->refresh();

    expect($room->current_occupancy)->toBe(2)
        ->and($room->status)->toBe('occupied')
        ->and($room->occupancyLabel())->toBe('2/2');
});

test('removing allocation resets room to vacant with 0/2', function () {
    $room = createHostelRoomForOccupancyTest();
    $student = createStudentForOccupancyTest();

    $allocation = HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $student->id,
    ]);

    $allocation->delete();

    $room->refresh();

    expect($room->current_occupancy)->toBe(0)
        ->and($room->status)->toBe('vacant')
        ->and($room->occupancyLabel())->toBe('0/2');
});

test('maintenance room updates occupancy but preserves maintenance status', function () {
    $room = createHostelRoomForOccupancyTest([
        'room' => ['status' => 'maintenance'],
    ]);
    $student = createStudentForOccupancyTest();

    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $student->id,
    ]);

    $room->refresh();

    expect($room->current_occupancy)->toBe(1)
        ->and($room->status)->toBe('maintenance')
        ->and($room->occupancyLabel())->toBe('1/2');
});

test('checkout date makes allocation inactive and lowers occupancy', function () {
    $room = createHostelRoomForOccupancyTest();
    $student = createStudentForOccupancyTest();

    $allocation = HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $student->id,
    ]);

    $allocation->update(['check_out' => now()->toDateString()]);

    $allocation->refresh();
    $room->refresh();

    expect($room->current_occupancy)->toBe(0)
        ->and($room->status)->toBe('vacant')
        ->and($allocation->status)->toBe(HostelAllocationStatusEnum::CHECKED_OUT);
});

test('rejects allocation when room is at capacity', function () {
    $room = createHostelRoomForOccupancyTest();
    $studentOne = createStudentForOccupancyTest();
    $studentTwo = createStudentForOccupancyTest();
    $studentThree = createStudentForOccupancyTest();

    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $studentOne->id,
    ]);

    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $studentTwo->id,
    ]);

    expect(fn () => HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $studentThree->id,
    ]))->toThrow(ValidationException::class);
});

test('pending allocation does not increase room occupancy', function () {
    $room = createHostelRoomForOccupancyTest();
    $student = createStudentForOccupancyTest();

    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $student->id,
        'status' => HostelAllocationStatusEnum::PENDING,
    ]);

    $room->refresh();

    expect($room->current_occupancy)->toBe(0)
        ->and($room->status)->toBe('vacant')
        ->and($room->occupancyLabel())->toBe('0/2');
});

test('rejects second active allocation for same student', function () {
    $roomOne = createHostelRoomForOccupancyTest(['room' => ['name' => 'T-0-01']]);
    $roomTwo = createHostelRoomForOccupancyTest(['room' => ['name' => 'T-0-02']]);
    $student = createStudentForOccupancyTest();

    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $roomOne->id,
        'student_id' => $student->id,
    ]);

    expect(fn () => HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $roomTwo->id,
        'student_id' => $student->id,
    ]))->toThrow(ValidationException::class);
});
