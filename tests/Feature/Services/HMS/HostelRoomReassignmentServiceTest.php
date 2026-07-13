<?php

use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\HMS\HostelApplicationTypeEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\HMS\Hostel;
use App\Models\HMS\HostelApplication;
use App\Models\HMS\HostelRoom;
use App\Models\HMS\HostelRoomAllocation;
use App\Models\HMS\HmsSetting;
use App\Services\HMS\HostelRoomReassignmentService;

it('can reassign an active hostel allocation to another room', function (): void {
    $student = createStudentForAllocationIndexTest();

    $hostel = Hostel::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'name' => 'Hostel D',
        'location' => 'West',
        'floor_count' => 2,
        'rooms_count' => 2,
        'capacity' => 4,
        'status' => 'active',
        'type' => 'male',
    ]);

    $firstRoom = HostelRoom::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_id' => $hostel->id,
        'name' => 'D-1-01',
        'room_type' => 'single',
        'capacity' => 1,
        'max_occupancy' => 1,
        'current_occupancy' => 0,
        'status' => 'vacant',
        'floor_number' => 1,
    ]);

    $secondRoom = HostelRoom::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_id' => $hostel->id,
        'name' => 'D-1-02',
        'room_type' => 'single',
        'capacity' => 1,
        'max_occupancy' => 1,
        'current_occupancy' => 0,
        'status' => 'vacant',
        'floor_number' => 1,
    ]);

    $allocation = HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $firstRoom->id,
        'student_id' => $student->id,
        'type' => 'direct',
        'status' => 'active',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]);

    app(HostelRoomReassignmentService::class)->reassign($allocation, (int) $secondRoom->id);

    expect($allocation->fresh()->hostel_room_id)->toBe($secondRoom->id);
});

it('declines expired awaiting payment applications via scheduled command', function (): void {
    $student = createStudentForAllocationIndexTest();

    HmsSetting::resolveForTenant(TenantEnum::HARARE_POLY->id())->update(['days_to_pay' => 3]);

    $application = HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'student_id' => $student->id,
        'gender_id' => $student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::AWAITING_PAYMENT,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
        'payment_due_at' => now()->subDay(),
    ]));

    $this->artisan('hms:expire-unpaid-applications')->assertSuccessful();

    expect($application->fresh()->status)->toBe(HostelApplicationStatusEnum::DECLINED)
        ->and($application->fresh()->decline_reason)->toBe(__('hms.payment_deadline_expired'));
});
