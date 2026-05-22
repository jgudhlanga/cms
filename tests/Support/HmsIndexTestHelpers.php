<?php

use App\Enums\Shared\TenantEnum;
use App\Models\HMS\Hostel;
use App\Models\HMS\HostelRoom;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Users\User;

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
