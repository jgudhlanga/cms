<?php

use App\Enums\HMS\HostelAllocationStatusEnum;
use App\Enums\HMS\HostelAllocationTypeEnum;
use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\HMS\HostelApplicationTypeEnum;
use App\Enums\Shared\TenantEnum;
use App\Jobs\HMS\SendHostelApplicationAwaitingPaymentEmail;
use App\Jobs\HMS\SendHostelApplicationDeclinedEmail;
use App\Models\HMS\HmsSetting;
use App\Models\HMS\HostelApplication;
use App\Models\HMS\HostelRoom;
use App\Models\HMS\HostelRoomAllocation;
use App\Models\Shared\Address;
use App\Models\Students\Student;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

test('json api hostel applications index returns paginated rows', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $student = createStudentForAllocationIndexTest();
    $gender = $student->gender;

    $application = HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'student_id' => $student->id,
        'gender_id' => $gender->id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::PENDING,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $student->update(['student_number' => 'APP-9001']);

    $response = $this
        ->jsonApi('hostel-applications')
        ->get(route('v1.json.hms.hostel-applications.index'));

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', (string) $application->id)
        ->assertJsonPath('data.0.attributes.studentNumber', 'APP-9001')
        ->assertJsonPath('data.0.attributes.applicationType', 'student')
        ->assertJsonPath('data.0.attributes.status', 'pending')
        ->assertJsonPath('data.0.attributes.gender', $gender->title);
});

test('json api hostel applications store creates guest application', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('create:hostel-applications');
    Sanctum::actingAs($user);

    HmsSetting::resolveForTenant($tenant->id)->update(['allow_guests' => true]);

    $student = createStudentForAllocationIndexTest();

    $response = $this
        ->jsonApi('hostel-applications')
        ->withData([
            'type' => 'hostel-applications',
            'attributes' => [
                'applicationType' => 'guest',
                'name' => 'Guest Visitor',
                'genderId' => $student->gender_id,
                'nextOfKinName' => 'Kin Name',
                'nextOfKinContact' => '0771234567',
                'checkIn' => now()->toDateString(),
                'checkOut' => now()->addWeek()->toDateString(),
            ],
        ])
        ->post(route('v1.json.hms.hostel-applications.store'));

    $response->assertCreated()
        ->assertJsonPath('data.attributes.applicationType', 'guest')
        ->assertJsonPath('data.attributes.displayName', 'Guest Visitor')
        ->assertJsonPath('data.attributes.gender', $student->gender->title);
});

test('json api hostel applications student lookup returns semester dates and capacity', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    HmsSetting::resolveForTenant($tenant->id);

    $studentProgram = createStudentReadyForHostelApplication('LOOKUP-001');
    $calendar = createRunningSemesterCalendar('2025/2026');
    ensureHostelRoomWithCapacity('Hostel D', 'D-LOOKUP-001');

    $response = $this
        ->jsonApi()
        ->get(route('v1.json.hostel-applications.studentLookup', ['filter' => ['search' => 'LOOKUP-001']]));

    $response->assertSuccessful()
        ->assertJsonPath('meta.found', true)
        ->assertJsonPath('meta.student.studentNumber', 'LOOKUP-001')
        ->assertJsonPath('meta.canSubmit', true)
        ->assertJsonPath('meta.semester.checkIn', Carbon::parse($calendar->opening_date)->toDateString())
        ->assertJsonPath('meta.semester.checkOut', Carbon::parse($calendar->closing_date)->toDateString())
        ->assertJsonPath('meta.roomAvailability.availableBeds', fn ($value) => $value > 0);
});

test('json api hostel applications student lookup blocks when no running semester', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    createStudentReadyForHostelApplication('LOOKUP-NO-SEM', withRunningSemester: false);
    ensureHostelRoomWithCapacity('Hostel D', 'D-LOOKUP-NO-SEM');

    $response = $this
        ->jsonApi()
        ->get(route('v1.json.hostel-applications.studentLookup', ['filter' => ['search' => 'LOOKUP-NO-SEM']]));

    $response->assertSuccessful()
        ->assertJsonPath('meta.canSubmit', false)
        ->assertJsonPath('meta.blockers', ['no_running_semester']);
});

test('json api hostel applications student lookup blocks when no hostel capacity', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    createStudentReadyForHostelApplication('LOOKUP-FULL');
    createRunningSemesterCalendar('2025/2026');

    $room = ensureHostelRoomWithCapacity('Hostel D', 'D-LOOKUP-FULL');
    $room->update(['max_occupancy' => 1, 'current_occupancy' => 1, 'status' => 'occupied']);

    $otherStudent = createStudentForAllocationIndexTest();
    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $otherStudent->id,
        'type' => 'direct',
        'status' => 'active',
        'check_in' => now()->toDateString(),
        'check_out' => null,
    ]);

    HostelRoom::query()->whereKey($room->id)->each(fn (HostelRoom $r) => $r->syncOccupancyFromAllocations());

    $response = $this
        ->jsonApi()
        ->get(route('v1.json.hostel-applications.studentLookup', ['filter' => ['search' => 'LOOKUP-FULL']]));

    $response->assertSuccessful()
        ->assertJsonPath('meta.canSubmit', false);

    expect($response->json('meta.blockers'))->toContain('no_hostel_capacity');
});

test('json api hostel applications student lookup blocks when student has pending application', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $studentProgram = createStudentReadyForHostelApplication('LOOKUP-PENDING');
    createRunningSemesterCalendar('2025/2026');
    ensureHostelRoomWithCapacity('Hostel D', 'D-LOOKUP-PENDING');

    HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'student_id' => $studentProgram->student_id,
        'gender_id' => $studentProgram->student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::PENDING,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $response = $this
        ->jsonApi()
        ->get(route('v1.json.hostel-applications.studentLookup', ['filter' => ['search' => 'LOOKUP-PENDING']]));

    $response->assertSuccessful()
        ->assertJsonPath('meta.canSubmit', false);

    expect($response->json('meta.blockers'))->toContain('pending_application_exists');
});

test('json api hostel applications store blocks duplicate pending student application', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('create:hostel-applications');
    Sanctum::actingAs($user);

    $studentProgram = createStudentReadyForHostelApplication('STORE-PENDING');
    createRunningSemesterCalendar('2025/2026');
    ensureHostelRoomWithCapacity('Hostel D', 'D-STORE-PENDING');
    $enrolmentId = $studentProgram->student->fresh(['latestEnrolment'])->latestEnrolment?->id;

    HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'student_id' => $studentProgram->student_id,
        'gender_id' => $studentProgram->student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::PENDING,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $response = $this
        ->jsonApi('hostel-applications')
        ->withData([
            'type' => 'hostel-applications',
            'attributes' => [
                'applicationType' => 'student',
                'studentId' => $studentProgram->student_id,
                'studentEnrolmentId' => $enrolmentId,
                'nextOfKinName' => 'Kin Name',
                'nextOfKinContact' => '0771234567',
                'checkIn' => now()->toDateString(),
                'checkOut' => now()->addMonths(4)->toDateString(),
            ],
        ])
        ->post(route('v1.json.hms.hostel-applications.store'));

    $response->assertUnprocessable();
});

test('json api hostel applications store blocks student application without running semester', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('create:hostel-applications');
    Sanctum::actingAs($user);

    $studentProgram = createStudentReadyForHostelApplication('STORE-BLOCK', withRunningSemester: false);
    ensureHostelRoomWithCapacity('Hostel D', 'D-STORE-BLOCK');
    $enrolmentId = $studentProgram->student->fresh(['latestEnrolment'])->latestEnrolment?->id;

    $response = $this
        ->jsonApi('hostel-applications')
        ->withData([
            'type' => 'hostel-applications',
            'attributes' => [
                'applicationType' => 'student',
                'studentId' => $studentProgram->student_id,
                'studentEnrolmentId' => $enrolmentId,
                'nextOfKinName' => 'Kin Name',
                'nextOfKinContact' => '0771234567',
                'checkIn' => now()->toDateString(),
                'checkOut' => now()->addMonths(4)->toDateString(),
            ],
        ])
        ->post(route('v1.json.hms.hostel-applications.store'));

    $response->assertUnprocessable();
});

test('json api hostel applications store creates student application with semester dates', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('create:hostel-applications');
    Sanctum::actingAs($user);

    $studentProgram = createStudentReadyForHostelApplication('STORE-OK');
    $calendar = createRunningSemesterCalendar('2025/2026');
    ensureHostelRoomWithCapacity('Hostel D', 'D-STORE-OK');

    $enrolmentId = $studentProgram->student->fresh(['latestEnrolment'])->latestEnrolment?->id;

    $response = $this
        ->jsonApi('hostel-applications')
        ->withData([
            'type' => 'hostel-applications',
            'attributes' => [
                'applicationType' => 'student',
                'studentId' => $studentProgram->student_id,
                'studentEnrolmentId' => $enrolmentId,
                'nextOfKinName' => 'Kin Name',
                'nextOfKinContact' => '0771234567',
                'checkIn' => '2000-01-01',
                'checkOut' => '2000-02-01',
            ],
        ])
        ->post(route('v1.json.hms.hostel-applications.store'));

    $response->assertCreated();

    $timezone = config('app.timezone');

    expect(Carbon::parse($response->json('data.attributes.checkIn'))->timezone($timezone)->toDateString())
        ->toBe(Carbon::parse($calendar->opening_date, $timezone)->toDateString())
        ->and(Carbon::parse($response->json('data.attributes.checkOut'))->timezone($timezone)->toDateString())
        ->toBe(Carbon::parse($calendar->closing_date, $timezone)->toDateString())
        ->and($response->json('data.attributes.gender'))->not->toBeEmpty();
});

test('json api hms settings update changes eligibility settings', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('update:hms-settings');
    Sanctum::actingAs($user);

    $settings = HmsSetting::resolveForTenant($tenant->id);

    $response = $this
        ->jsonApi('hms-settings')
        ->withData([
            'type' => 'hms-settings',
            'id' => (string) $settings->id,
            'attributes' => [
                'campusCity' => 'Bulawayo',
                'requireFullTimeStudy' => false,
                'allowGuests' => true,
            ],
        ])
        ->patch(route('v1.json.hms.hms-settings.update', $settings));

    $response->assertSuccessful()
        ->assertJsonPath('data.attributes.campusCity', 'Bulawayo')
        ->assertJsonPath('data.attributes.requireFullTimeStudy', false)
        ->assertJsonPath('data.attributes.allowGuests', true);
});

test('json api hostel applications update can approve pending application', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('update:hostel-applications');
    Sanctum::actingAs($user);

    $student = createStudentForAllocationIndexTest();
    $room = ensureHostelRoomWithCapacity('Hostel D', 'APPROVE-01');
    $room->hostel->update(['type' => 'male']);

    $application = HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'student_id' => $student->id,
        'gender_id' => $student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::PENDING,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $response = $this
        ->jsonApi('hostel-applications')
        ->withData([
            'type' => 'hostel-applications',
            'id' => (string) $application->id,
            'attributes' => [
                'status' => 'approved',
                'hostelRoomId' => $room->id,
            ],
        ])
        ->patch(route('v1.json.hms.hostel-applications.update', $application));

    $response->assertSuccessful()
        ->assertJsonPath('data.attributes.status', 'approved')
        ->assertJsonMissingPath('data.relationships.student');

    expect($application->fresh()->status)->toBe(HostelApplicationStatusEnum::APPROVED)
        ->and(HostelRoomAllocation::query()
            ->where('student_id', $student->id)
            ->where('hostel_room_id', $room->id)
            ->active()
            ->exists())->toBeTrue();
});

test('json api hostel applications approve requires hostel room id', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('update:hostel-applications');
    Sanctum::actingAs($user);

    $student = createStudentForAllocationIndexTest();
    $application = HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'student_id' => $student->id,
        'gender_id' => $student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::PENDING,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $this
        ->jsonApi('hostel-applications')
        ->withData([
            'type' => 'hostel-applications',
            'id' => (string) $application->id,
            'attributes' => [
                'status' => 'approved',
            ],
        ])
        ->patch(route('v1.json.hms.hostel-applications.update', $application))
        ->assertStatus(422);
});

test('json api hostel applications store rejects guest when allow guests is false', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('create:hostel-applications');
    Sanctum::actingAs($user);

    HmsSetting::resolveForTenant($tenant->id)->update(['allow_guests' => false]);

    $student = createStudentForAllocationIndexTest();

    $this
        ->jsonApi('hostel-applications')
        ->withData([
            'type' => 'hostel-applications',
            'attributes' => [
                'applicationType' => 'guest',
                'name' => 'Guest Visitor',
                'genderId' => $student->gender_id,
                'nextOfKinName' => 'Kin Name',
                'nextOfKinContact' => '0771234567',
                'checkIn' => now()->toDateString(),
                'checkOut' => now()->addWeek()->toDateString(),
            ],
        ])
        ->post(route('v1.json.hms.hostel-applications.store'))
        ->assertStatus(422);
});

test('json api hostel applications approval options returns gender hostels and partial rooms', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $student = createStudentForAllocationIndexTest();
    $room = ensureHostelRoomWithCapacity('Hostel D', 'OPTS-01');
    $room->hostel->update(['type' => 'male']);
    $room->update(['max_occupancy' => 2, 'current_occupancy' => 1, 'status' => 'occupied']);
    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => createStudentForAllocationIndexTest()->id,
        'type' => HostelAllocationTypeEnum::DIRECT,
        'status' => HostelAllocationStatusEnum::ACTIVE,
        'check_in' => now()->toDateString(),
        'check_out' => null,
    ]);

    $application = HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'student_id' => $student->id,
        'gender_id' => $student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::PENDING,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $hostelsResponse = $this->getJson(route('v1.json.hostel-applications.approvalOptions', [
        'hostel_application' => $application->id,
    ]));

    $hostelsResponse->assertSuccessful()
        ->assertJsonPath('meta.canApprove', true)
        ->assertJsonPath('meta.hostels.0.name', 'Hostel D');

    $roomsResponse = $this->getJson(route('v1.json.hostel-applications.approvalOptions', [
        'hostel_application' => $application->id,
        'hostelId' => $room->hostel_id,
    ]));

    $roomsResponse->assertSuccessful()
        ->assertJsonPath('meta.rooms.0.occupancyLabel', '1/2');
});

test('json api hostel applications index sorts pending applications first', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $student = createStudentForAllocationIndexTest();

    $approved = HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'student_id' => $student->id,
        'gender_id' => $student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::APPROVED,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
        'created_at' => now()->subDay(),
    ]));

    $pending = HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'student_id' => $student->id,
        'gender_id' => $student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::PENDING,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
        'created_at' => now(),
    ]));

    $response = $this
        ->jsonApi('hostel-applications')
        ->get(route('v1.json.hms.hostel-applications.index'));

    $response->assertSuccessful()
        ->assertJsonPath('data.0.id', (string) $pending->id)
        ->assertJsonPath('data.1.id', (string) $approved->id);
});

test('json api hostel applications update can move pending student application to awaiting payment', function () {
    Queue::fake();

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('update:hostel-applications');
    Sanctum::actingAs($user);

    $student = createStudentForAllocationIndexTest();
    $application = HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'student_id' => $student->id,
        'gender_id' => $student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::PENDING,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $this
        ->jsonApi('hostel-applications')
        ->withData([
            'type' => 'hostel-applications',
            'id' => (string) $application->id,
            'attributes' => [
                'status' => 'awaiting-payment',
            ],
        ])
        ->patch(route('v1.json.hms.hostel-applications.update', $application))
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.status', 'awaiting-payment');

    expect($application->fresh()->status)->toBe(HostelApplicationStatusEnum::AWAITING_PAYMENT)
        ->and(HostelRoomAllocation::query()->where('student_id', $student->id)->exists())->toBeFalse();

    Queue::assertPushed(SendHostelApplicationAwaitingPaymentEmail::class);
});

test('json api hostel applications update decline queues rejection email', function () {
    Queue::fake();

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('update:hostel-applications');
    Sanctum::actingAs($user);

    $student = createStudentForAllocationIndexTest();
    $application = HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'student_id' => $student->id,
        'gender_id' => $student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::PENDING,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $this
        ->jsonApi('hostel-applications')
        ->withData([
            'type' => 'hostel-applications',
            'id' => (string) $application->id,
            'attributes' => [
                'status' => 'declined',
                'declineReason' => 'Incomplete documentation',
            ],
        ])
        ->patch(route('v1.json.hms.hostel-applications.update', $application))
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.status', 'declined')
        ->assertJsonPath('data.attributes.declineReason', 'Incomplete documentation');

    Queue::assertPushed(SendHostelApplicationDeclinedEmail::class);
});

test('json api hostel applications show returns department calendar year and physical address', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $studentProgram = createStudentReadyForHostelApplication('SHOW-ATTR-01');
    $student = $studentProgram->student->fresh(['latestEnrolment.institutionDepartment.department']);
    $enrolment = $student->latestEnrolment;
    $calendarYear = (string) ($studentProgram->intakePeriod?->calendar_year ?? '2025/2026');
    $departmentName = $enrolment?->institutionDepartment?->department?->name;

    Address::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'addressable_type' => Student::class,
        'addressable_id' => $student->id,
        'address_1' => '12 Main Street',
        'address_2' => 'Harare',
    ]);

    $application = HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'student_id' => $student->id,
        'student_enrolment_id' => $enrolment?->id,
        'gender_id' => $student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::PENDING,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $this
        ->jsonApi('hostel-applications')
        ->get(route('v1.json.hms.hostel-applications.show', $application))
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.physicalAddress', '12 Main Street, Harare')
        ->assertJsonPath('data.attributes.calendarYear', $calendarYear)
        ->assertJsonPath('data.attributes.departmentName', $departmentName);
});

test('json api hostel applications student lookup blocks when student has awaiting payment application', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $studentProgram = createStudentReadyForHostelApplication('LOOKUP-AWAITING');
    createRunningSemesterCalendar('2025/2026');
    ensureHostelRoomWithCapacity('Hostel D', 'D-LOOKUP-AWAITING');

    HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'student_id' => $studentProgram->student_id,
        'gender_id' => $studentProgram->student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::AWAITING_PAYMENT,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $response = $this
        ->jsonApi()
        ->get(route('v1.json.hostel-applications.studentLookup', ['filter' => ['search' => 'LOOKUP-AWAITING']]));

    $response->assertSuccessful()
        ->assertJsonPath('meta.canSubmit', false);

    expect($response->json('meta.blockers'))->toContain('pending_application_exists');
});

test('hostels applications show page is accessible for authorized users', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('view:hostel-applications');

    $student = createStudentForAllocationIndexTest();
    $application = HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'student_id' => $student->id,
        'gender_id' => $student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::PENDING,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $this->actingAs($user)
        ->get(route('hostels.applications.show', $application))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('hms/applications/Show')
            ->where('applicationId', $application->id));
});

test('json api hostel applications store requires authentication', function () {
    $this
        ->jsonApi('hostel-applications')
        ->withData([
            'type' => 'hostel-applications',
            'attributes' => [
                'applicationType' => 'guest',
                'name' => 'Guest Visitor',
            ],
        ])
        ->post(route('v1.json.hms.hostel-applications.store'))
        ->assertUnauthorized();
});
