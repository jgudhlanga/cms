<?php

use App\Enums\HMS\HostelAllocationStatusEnum;
use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\HMS\HostelApplicationTypeEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\HMS\HostelAmenity;
use App\Models\HMS\HostelApplication;
use App\Models\HMS\HostelRoomAllocation;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Laravel\Sanctum\Sanctum;

test('json api hostel allocations can be filtered by student for staff', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('viewAny:hostel-room-allocations');
    Sanctum::actingAs($user);

    $student = createStudentForAllocationIndexTest();
    $room = ensureHostelRoomWithCapacity('Hostel D', 'FILTER-STU-01');

    $allocation = HostelRoomAllocation::withoutEvents(fn () => HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $student->id,
        'type' => 'direct',
        'status' => HostelAllocationStatusEnum::ACTIVE,
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $otherStudent = createStudentForAllocationIndexTest();

    HostelRoomAllocation::withoutEvents(fn () => HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $otherStudent->id,
        'type' => 'direct',
        'status' => HostelAllocationStatusEnum::ACTIVE,
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $this
        ->jsonApi('hostel-room-allocations')
        ->filter(['student' => (string) $student->id])
        ->get(route('v1.json.hms.hostel-room-allocations.index'))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', (string) $allocation->id);
});

test('json api portal student cannot view another students allocations', function () {
    $tenant = Tenant::query()->firstOrFail();
    $portalUser = User::factory()->create(['tenant_id' => $tenant->id]);
    $portalUser->givePermissionTo('manageOwnStudentAccommodationDetails:students');

    $student = createStudentForAllocationIndexTest();
    $student->update(['user_id' => $portalUser->id]);
    Sanctum::actingAs($portalUser);

    $otherStudent = createStudentForAllocationIndexTest();

    $this
        ->jsonApi('hostel-room-allocations')
        ->filter(['student' => (string) $otherStudent->id])
        ->get(route('v1.json.hms.hostel-room-allocations.index'))
        ->assertForbidden();
});

test('json api portal student can self lookup and apply for hostel', function () {
    $tenant = Tenant::query()->firstOrFail();
    $portalUser = User::factory()->create(['tenant_id' => $tenant->id]);
    $portalUser->givePermissionTo('manageOwnStudentAccommodationDetails:students');
    Sanctum::actingAs($portalUser);

    disableAllHmsApprovalRequirements($tenant->id);
    openHostelApplications($tenant->id);

    $studentApplication = createStudentReadyForHostelApplication('PORTAL-APPLY-01');
    $student = $studentApplication->student;
    $student->update(['user_id' => $portalUser->id]);

    ensureHostelRoomWithCapacity('Hostel D', 'PORTAL-APPLY-ROOM');

    $this
        ->jsonApi()
        ->get(route('v1.json.hostel-applications.selfLookup'))
        ->assertSuccessful()
        ->assertJsonPath('meta.found', true)
        ->assertJsonPath('meta.canApply', true);

    $this
        ->jsonApi('hostel-applications')
        ->withData([
            'type' => 'hostel-applications',
            'attributes' => [
                'applicationType' => 'student',
                'studentId' => $student->id,
                'nextOfKinName' => 'Jane Kin',
                'nextOfKinContact' => '0771112233',
            ],
        ])
        ->post(route('v1.json.hms.hostel-applications.store'))
        ->assertCreated();

    $this
        ->jsonApi('hostel-applications')
        ->withData([
            'type' => 'hostel-applications',
            'attributes' => [
                'applicationType' => 'student',
                'studentId' => $student->id,
                'nextOfKinName' => 'Jane Kin',
                'nextOfKinContact' => '0771112233',
            ],
        ])
        ->post(route('v1.json.hms.hostel-applications.store'))
        ->assertStatus(422);
});

test('json api student lookup by id works for staff with view students', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('view:students');
    Sanctum::actingAs($user);

    disableAllHmsApprovalRequirements($tenant->id);

    $studentApplication = createStudentReadyForHostelApplication('LOOKUP-ID-01');
    ensureHostelRoomWithCapacity('Hostel D', 'LOOKUP-ID-ROOM');

    $this
        ->jsonApi()
        ->get(route('v1.json.hostel-applications.studentLookup', [
            'filter' => ['student' => (string) $studentApplication->student_id],
        ]))
        ->assertSuccessful()
        ->assertJsonPath('meta.found', true)
        ->assertJsonPath('meta.student.id', $studentApplication->student_id);
});

test('json api accommodation fees meta returns summary for student', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('view:students');
    Sanctum::actingAs($user);

    $studentApplication = createStudentReadyForHostelApplication('FEES-META-01');

    $this
        ->jsonApi()
        ->get(route('v1.json.hostel-applications.accommodationFees', [
            'filter' => ['student' => (string) $studentApplication->student_id],
        ]))
        ->assertSuccessful()
        ->assertJsonStructure([
            'meta' => ['total', 'paid', 'due', 'isFullyPaid', 'paymentHistory'],
        ]);
});

test('json api allocation roommates excludes current student', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('viewAny:hostel-room-allocations');
    Sanctum::actingAs($user);

    $room = ensureHostelRoomWithCapacity('Hostel D', 'ROOM-MATE-01');
    $studentA = createStudentForAllocationIndexTest();
    $studentB = createStudentForAllocationIndexTest();

    $allocationA = HostelRoomAllocation::withoutEvents(fn () => HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $studentA->id,
        'type' => 'direct',
        'status' => HostelAllocationStatusEnum::ACTIVE,
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    HostelRoomAllocation::withoutEvents(fn () => HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $studentB->id,
        'type' => 'direct',
        'status' => HostelAllocationStatusEnum::ACTIVE,
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $this
        ->jsonApi()
        ->get(route('v1.json.hostel-room-allocations.roommates', $allocationA->id))
        ->assertSuccessful()
        ->assertJsonCount(1, 'meta.roommates')
        ->assertJsonPath('meta.roommates.0.studentId', $studentB->id);
});

test('json api allocation includes amenities when attached to room', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('viewAny:hostel-room-allocations');
    Sanctum::actingAs($user);

    $amenity = HostelAmenity::query()->firstOrCreate(
        [
            'tenant_id' => TenantEnum::HARARE_POLY->id(),
            'slug' => 'bed',
        ],
        ['name' => 'Bed'],
    );

    $student = createStudentForAllocationIndexTest();
    $room = ensureHostelRoomWithCapacity('Hostel D', 'AMENITY-ROOM');
    $room->amenities()->sync([$amenity->id]);

    $allocation = HostelRoomAllocation::withoutEvents(fn () => HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $student->id,
        'type' => 'direct',
        'status' => HostelAllocationStatusEnum::ACTIVE,
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $this
        ->jsonApi('hostel-room-allocations')
        ->filter(['student' => (string) $student->id])
        ->get(route('v1.json.hms.hostel-room-allocations.index'))
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', (string) $allocation->id)
        ->assertJsonPath('data.0.attributes.amenities.0', 'Bed');
});

test('json api hostel applications can be filtered by student', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('viewAny:hostel-applications');
    Sanctum::actingAs($user);

    $student = createStudentForAllocationIndexTest();

    $application = HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'student_id' => $student->id,
        'gender_id' => $student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::PENDING,
        'next_of_kin_name' => 'Kin',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $this
        ->jsonApi('hostel-applications')
        ->filter(['student' => (string) $student->id])
        ->get(route('v1.json.hms.hostel-applications.index'))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', (string) $application->id);
});
