<?php

use App\Enums\HMS\HostelAllocationStatusEnum;
use App\Enums\HMS\HostelAllocationTypeEnum;
use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\HMS\HostelApplicationTypeEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\HMS\Hostel;
use App\Models\HMS\HostelApplication;
use App\Models\HMS\HostelRoom;
use App\Models\HMS\HostelRoomAllocation;
use App\Services\HMS\HostelApplicationApprovalService;
use App\Services\HMS\HostelRoomSectionService;
use Illuminate\Validation\ValidationException;

it('auto allocates non-disabled students from the top floor downward before ground floor', function (): void {
    $tenantId = TenantEnum::HARARE_POLY->id();
    $student = createStudentForAllocationIndexTest();

    $hostel = Hostel::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Hostel D',
        'location' => 'West',
        'floor_count' => 4,
        'rooms_count' => 2,
        'capacity' => 4,
        'status' => 'active',
        'type' => 'male',
    ]);

    $groundRoom = HostelRoom::query()->create([
        'tenant_id' => $tenantId,
        'hostel_id' => $hostel->id,
        'name' => 'D-G-01',
        'room_type' => 'double',
        'capacity' => 2,
        'max_occupancy' => 2,
        'current_occupancy' => 0,
        'status' => 'vacant',
        'floor_number' => 0,
    ]);

    $topRoom = HostelRoom::query()->create([
        'tenant_id' => $tenantId,
        'hostel_id' => $hostel->id,
        'name' => 'D-3-01',
        'room_type' => 'double',
        'capacity' => 2,
        'max_occupancy' => 2,
        'current_occupancy' => 0,
        'status' => 'vacant',
        'floor_number' => 3,
    ]);

    disableAllHmsApprovalRequirements($tenantId)->update(['auto_allocate_rooms' => true]);

    $application = HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => $tenantId,
        'student_id' => $student->id,
        'gender_id' => $student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::PENDING,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    app(HostelApplicationApprovalService::class)->approve($application);

    expect(HostelRoomAllocation::query()->where('student_id', $student->id)->latest('id')->value('hostel_room_id'))
        ->toBe($topRoom->id)
        ->not->toBe($groundRoom->id);
});

it('auto allocates disabled students to the ground floor first', function (): void {
    $tenantId = TenantEnum::HARARE_POLY->id();
    $student = createStudentForAllocationIndexTest();
    $student->update(['disability_status' => 'yes']);

    $hostel = Hostel::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Hostel E',
        'location' => 'West',
        'floor_count' => 4,
        'rooms_count' => 2,
        'capacity' => 4,
        'status' => 'active',
        'type' => 'male',
    ]);

    $groundRoom = HostelRoom::query()->create([
        'tenant_id' => $tenantId,
        'hostel_id' => $hostel->id,
        'name' => 'E-G-01',
        'room_type' => 'double',
        'capacity' => 2,
        'max_occupancy' => 2,
        'current_occupancy' => 0,
        'status' => 'vacant',
        'floor_number' => 0,
    ]);

    HostelRoom::query()->create([
        'tenant_id' => $tenantId,
        'hostel_id' => $hostel->id,
        'name' => 'E-3-01',
        'room_type' => 'double',
        'capacity' => 2,
        'max_occupancy' => 2,
        'current_occupancy' => 0,
        'status' => 'vacant',
        'floor_number' => 3,
    ]);

    disableAllHmsApprovalRequirements($tenantId)->update(['auto_allocate_rooms' => true]);

    $application = HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => $tenantId,
        'student_id' => $student->id,
        'gender_id' => $student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::PENDING,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    app(HostelApplicationApprovalService::class)->approve($application);

    expect(HostelRoomAllocation::query()->where('student_id', $student->id)->latest('id')->value('hostel_room_id'))
        ->toBe($groundRoom->id);
});

it('assigns the first free room section when approving an application', function (): void {
    $tenantId = TenantEnum::HARARE_POLY->id();
    $student = createStudentForAllocationIndexTest();

    $hostel = Hostel::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Hostel F',
        'location' => 'East',
        'floor_count' => 2,
        'rooms_count' => 1,
        'capacity' => 2,
        'status' => 'active',
        'type' => 'male',
    ]);

    $room = HostelRoom::query()->create([
        'tenant_id' => $tenantId,
        'hostel_id' => $hostel->id,
        'name' => 'F-1-01',
        'room_type' => 'double',
        'capacity' => 2,
        'max_occupancy' => 2,
        'current_occupancy' => 0,
        'status' => 'vacant',
        'floor_number' => 1,
    ]);

    disableAllHmsApprovalRequirements($tenantId)->update(['auto_allocate_rooms' => true]);

    $application = HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => $tenantId,
        'student_id' => $student->id,
        'gender_id' => $student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::PENDING,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    app(HostelApplicationApprovalService::class)->approve($application);

    $allocation = HostelRoomAllocation::query()->where('student_id', $student->id)->latest('id')->first();
    $sectionA = $room->sections()->where('name', 'A')->first();

    expect($allocation)->not->toBeNull()
        ->and($allocation->hostel_room_id)->toBe($room->id)
        ->and($allocation->hostel_room_section_id)->toBe($sectionA?->id)
        ->and($room->sections()->orderBy('name')->pluck('name')->all())->toBe(['A', 'B']);
});

it('assigns the next free section when the first is already occupied', function (): void {
    $tenantId = TenantEnum::HARARE_POLY->id();
    $firstStudent = createStudentForAllocationIndexTest();
    $secondStudent = createStudentForAllocationIndexTest();

    $hostel = Hostel::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Hostel G',
        'location' => 'East',
        'floor_count' => 2,
        'rooms_count' => 1,
        'capacity' => 2,
        'status' => 'active',
        'type' => 'male',
    ]);

    $room = HostelRoom::query()->create([
        'tenant_id' => $tenantId,
        'hostel_id' => $hostel->id,
        'name' => 'G-1-01',
        'room_type' => 'double',
        'capacity' => 2,
        'max_occupancy' => 2,
        'current_occupancy' => 0,
        'status' => 'vacant',
        'floor_number' => 1,
    ]);

    $sections = app(HostelRoomSectionService::class)->ensureSectionsForRoom($room);
    $sectionA = $sections->firstWhere('name', 'A');
    $sectionB = $sections->firstWhere('name', 'B');

    HostelRoomAllocation::query()->create([
        'tenant_id' => $tenantId,
        'hostel_room_id' => $room->id,
        'hostel_room_section_id' => $sectionA->id,
        'student_id' => $firstStudent->id,
        'type' => HostelAllocationTypeEnum::DIRECT,
        'status' => HostelAllocationStatusEnum::ACTIVE,
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]);

    disableAllHmsApprovalRequirements($tenantId)->update(['auto_allocate_rooms' => true]);

    $application = HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => $tenantId,
        'student_id' => $secondStudent->id,
        'gender_id' => $secondStudent->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::PENDING,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    app(HostelApplicationApprovalService::class)->approve($application);

    $allocation = HostelRoomAllocation::query()->where('student_id', $secondStudent->id)->latest('id')->first();

    expect($allocation->hostel_room_section_id)->toBe($sectionB->id);
});

it('rejects a second active allocation on the same room section', function (): void {
    $tenantId = TenantEnum::HARARE_POLY->id();
    $firstStudent = createStudentForAllocationIndexTest();
    $secondStudent = createStudentForAllocationIndexTest();

    $hostel = Hostel::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Hostel H',
        'location' => 'East',
        'floor_count' => 2,
        'rooms_count' => 1,
        'capacity' => 2,
        'status' => 'active',
        'type' => 'male',
    ]);

    $room = HostelRoom::query()->create([
        'tenant_id' => $tenantId,
        'hostel_id' => $hostel->id,
        'name' => 'H-1-01',
        'room_type' => 'double',
        'capacity' => 2,
        'max_occupancy' => 2,
        'current_occupancy' => 0,
        'status' => 'vacant',
        'floor_number' => 1,
    ]);

    $sectionA = app(HostelRoomSectionService::class)
        ->ensureSectionsForRoom($room)
        ->firstWhere('name', 'A');

    HostelRoomAllocation::query()->create([
        'tenant_id' => $tenantId,
        'hostel_room_id' => $room->id,
        'hostel_room_section_id' => $sectionA->id,
        'student_id' => $firstStudent->id,
        'type' => HostelAllocationTypeEnum::DIRECT,
        'status' => HostelAllocationStatusEnum::ACTIVE,
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]);

    expect(fn () => HostelRoomAllocation::query()->create([
        'tenant_id' => $tenantId,
        'hostel_room_id' => $room->id,
        'hostel_room_section_id' => $sectionA->id,
        'student_id' => $secondStudent->id,
        'type' => HostelAllocationTypeEnum::DIRECT,
        'status' => HostelAllocationStatusEnum::ACTIVE,
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]))->toThrow(ValidationException::class);
});
