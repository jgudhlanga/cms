<?php

use App\Enums\HMS\HostelAllocationStatusEnum;
use App\Enums\HMS\HostelAllocationTypeEnum;
use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\HMS\HostelApplicationTypeEnum;
use App\Enums\Shared\TenantEnum;
use App\Jobs\HMS\SendHostelApplicationAwaitingPaymentEmail;
use App\Jobs\HMS\SendHostelApplicationDeclinedEmail;
use App\Jobs\HMS\SendHostelRoomAllocationEmail;
use App\Mail\HMS\HostelRoomAllocationConfirmedMail;
use App\Models\HMS\HmsSetting;
use App\Models\HMS\HostelApplication;
use App\Models\HMS\HostelRoom;
use App\Models\HMS\HostelRoomAllocation;
use App\Models\Shared\Address;
use App\Models\Students\Student;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
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
        ->assertJsonPath('data.attributes.requireAccommodationPaid', true)
        ->assertJsonPath('data.attributes.allowGuests', true);
});

test('json api hms settings update can disable accommodation fee requirement', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('update:hms-settings');
    Sanctum::actingAs($user);

    $settings = HmsSetting::resolveForTenant($tenant->id);

    $this
        ->jsonApi('hms-settings')
        ->withData([
            'type' => 'hms-settings',
            'id' => (string) $settings->id,
            'attributes' => [
                'requireAccommodationPaid' => false,
            ],
        ])
        ->patch(route('v1.json.hms.hms-settings.update', $settings))
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.requireAccommodationPaid', false);
});

test('json api hostel applications student lookup includes accommodation eligibility when required', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    HmsSetting::resolveForTenant($tenant->id)->update(['require_accommodation_paid' => true]);

    createStudentReadyForHostelApplication('LOOKUP-ACC-01');
    createRunningSemesterCalendar('2025/2026');
    ensureHostelRoomWithCapacity('Hostel D', 'D-LOOKUP-ACC-01');

    $response = $this
        ->jsonApi()
        ->get(route('v1.json.hostel-applications.studentLookup', ['filter' => ['search' => 'LOOKUP-ACC-01']]));

    $keys = collect($response->json('meta.eligibility'))->pluck('key')->all();

    expect($keys)->toContain('accommodation_paid');
});

test('json api hostel applications approval options returns required payment verification from settings', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    HmsSetting::resolveForTenant($tenant->id)->update([
        'require_full_time_study' => true,
        'require_tuition_paid' => false,
        'require_accommodation_paid' => true,
        'require_address_outside_campus' => false,
    ]);

    $student = createStudentForAllocationIndexTest();
    ensureHostelRoomWithCapacity('Hostel D', 'OPTS-REQ-01')->hostel->update(['type' => 'male']);

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
    ]));

    $this->getJson(route('v1.json.hostel-applications.approvalOptions', [
        'hostel_application' => $application->id,
    ]))
        ->assertSuccessful()
        ->assertJsonPath('meta.requiredPaymentVerification', [
            'fullTimeStudentConfirmed',
            'accommodationFeesPaidConfirmed',
        ]);
});

test('json api hostel applications can approve without payment verification when all requirements disabled', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('update:hostel-applications');
    Sanctum::actingAs($user);

    disableAllHmsApprovalRequirements($tenant->id);

    $student = createStudentForAllocationIndexTest();
    $room = ensureHostelRoomWithCapacity('Hostel D', 'APPROVE-NO-VERIFY');
    $room->hostel->update(['type' => 'male']);

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
    ]));

    $this
        ->jsonApi('hostel-applications')
        ->withData([
            'type' => 'hostel-applications',
            'id' => (string) $application->id,
            'attributes' => [
                'status' => 'approved',
                'hostelRoomId' => $room->id,
            ],
        ])
        ->patch(route('v1.json.hms.hostel-applications.update', $application))
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.status', 'approved');
});

test('json api hostel applications approve requires only enabled payment verification keys', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('update:hostel-applications');
    Sanctum::actingAs($user);

    HmsSetting::resolveForTenant($tenant->id)->update([
        'require_full_time_study' => false,
        'require_tuition_paid' => true,
        'require_accommodation_paid' => false,
        'require_address_outside_campus' => false,
    ]);

    $student = createStudentForAllocationIndexTest();
    $room = ensureHostelRoomWithCapacity('Hostel D', 'APPROVE-PARTIAL');
    $room->hostel->update(['type' => 'male']);

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
    ]));

    $this
        ->jsonApi('hostel-applications')
        ->withData([
            'type' => 'hostel-applications',
            'id' => (string) $application->id,
            'attributes' => [
                'status' => 'approved',
                'hostelRoomId' => $room->id,
            ],
        ])
        ->patch(route('v1.json.hms.hostel-applications.update', $application))
        ->assertStatus(422);

    $this
        ->jsonApi('hostel-applications')
        ->withData([
            'type' => 'hostel-applications',
            'id' => (string) $application->id,
            'attributes' => [
                'status' => 'approved',
                'hostelRoomId' => $room->id,
                'paymentVerification' => [
                    'tuitionFeesPaidConfirmed' => true,
                ],
            ],
        ])
        ->patch(route('v1.json.hms.hostel-applications.update', $application))
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.status', 'approved');
});

test('json api hostel applications update can approve awaiting payment application', function () {
    Queue::fake();

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('update:hostel-applications');
    Sanctum::actingAs($user);

    HmsSetting::resolveForTenant($tenant->id)->update([
        'require_full_time_study' => true,
        'require_tuition_paid' => true,
        'require_accommodation_paid' => true,
        'require_address_outside_campus' => true,
    ]);

    $student = createStudentForAllocationIndexTest();
    $room = ensureHostelRoomWithCapacity('Hostel D', 'APPROVE-01');
    $room->hostel->update(['type' => 'male']);

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
    ]));

    $response = $this
        ->jsonApi('hostel-applications')
        ->withData([
            'type' => 'hostel-applications',
            'id' => (string) $application->id,
            'attributes' => [
                'status' => 'approved',
                'hostelRoomId' => $room->id,
                'paymentVerification' => [
                    'addressOutsideCityCampusConfirmed' => true,
                    'fullTimeStudentConfirmed' => true,
                    'tuitionFeesPaidConfirmed' => true,
                    'accommodationFeesPaidConfirmed' => true,
                ],
            ],
        ])
        ->patch(route('v1.json.hms.hostel-applications.update', $application));

    $response->assertSuccessful()
        ->assertJsonPath('data.attributes.status', 'approved')
        ->assertJsonPath('data.attributes.paymentVerification.addressOutsideCityCampusConfirmed', true)
        ->assertJsonMissingPath('data.relationships.student');

    $application->refresh();

    $allocation = HostelRoomAllocation::query()
        ->where('student_id', $student->id)
        ->where('hostel_room_id', $room->id)
        ->where('status', HostelAllocationStatusEnum::ACTIVE)
        ->first();

    expect($application->status)->toBe(HostelApplicationStatusEnum::APPROVED)
        ->and($allocation)->not->toBeNull()
        ->and($allocation->check_in?->toDateString())->toBe($application->check_in?->toDateString())
        ->and($allocation->check_out?->toDateString())->toBe($application->check_out?->toDateString());

    Queue::assertPushed(SendHostelRoomAllocationEmail::class);
});

test('json api hostel applications approve queues room allocation email with room details', function () {
    Mail::fake();
    Queue::fake();

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('update:hostel-applications');
    Sanctum::actingAs($user);

    HmsSetting::resolveForTenant($tenant->id)->update([
        'require_full_time_study' => false,
        'require_tuition_paid' => false,
        'require_accommodation_paid' => false,
        'require_address_outside_campus' => false,
    ]);

    $student = createStudentForAllocationIndexTest();
    $student->load('user');
    $room = ensureHostelRoomWithCapacity('Hostel D', 'ALLOC-MAIL-01');
    $room->hostel->update(['type' => 'male', 'name' => 'Block D Test Hostel']);
    $room->update(['name' => 'Room 101', 'floor_number' => '2', 'room_type' => 'double']);

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
    ]));

    $this
        ->jsonApi('hostel-applications')
        ->withData([
            'type' => 'hostel-applications',
            'id' => (string) $application->id,
            'attributes' => [
                'status' => 'approved',
                'hostelRoomId' => $room->id,
                'paymentVerification' => [],
            ],
        ])
        ->patch(route('v1.json.hms.hostel-applications.update', $application))
        ->assertSuccessful();

    (new SendHostelRoomAllocationEmail($application->id))->handle();

    Mail::assertSent(HostelRoomAllocationConfirmedMail::class, function (HostelRoomAllocationConfirmedMail $mail) use ($student): bool {
        $html = $mail->render();

        return $mail->hasTo($student->user->email)
            && str_contains($html, 'Block D Test Hostel')
            && str_contains($html, 'Room 101')
            && str_contains($html, '2');
    });
});

test('json api hostel applications approve does not queue room allocation email without recipient email', function () {
    Queue::fake();

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('update:hostel-applications');
    Sanctum::actingAs($user);

    HmsSetting::resolveForTenant($tenant->id)->update([
        'require_full_time_study' => false,
        'require_tuition_paid' => false,
        'require_accommodation_paid' => false,
        'require_address_outside_campus' => false,
    ]);

    $student = createStudentForAllocationIndexTest();
    $student->user->update(['email' => '']);
    $room = ensureHostelRoomWithCapacity('Hostel D', 'ALLOC-NO-EMAIL');
    $room->hostel->update(['type' => 'male']);

    $application = HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'student_id' => $student->id,
        'gender_id' => $student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::AWAITING_PAYMENT,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'email_address' => null,
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
                'hostelRoomId' => $room->id,
                'paymentVerification' => [],
            ],
        ])
        ->patch(route('v1.json.hms.hostel-applications.update', $application))
        ->assertSuccessful();

    Queue::assertNotPushed(SendHostelRoomAllocationEmail::class);
});

test('json api hostel applications approve requires payment verification and hostel room', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('update:hostel-applications');
    Sanctum::actingAs($user);

    HmsSetting::resolveForTenant($tenant->id)->update([
        'require_full_time_study' => true,
        'require_tuition_paid' => true,
        'require_accommodation_paid' => true,
        'require_address_outside_campus' => true,
    ]);

    $student = createStudentForAllocationIndexTest();
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

    $room = ensureHostelRoomWithCapacity('Hostel D', 'APPROVE-02');
    $room->hostel->update(['type' => 'male']);

    $this
        ->jsonApi('hostel-applications')
        ->withData([
            'type' => 'hostel-applications',
            'id' => (string) $application->id,
            'attributes' => [
                'status' => 'approved',
                'hostelRoomId' => $room->id,
                'paymentVerification' => [
                    'addressOutsideCityCampusConfirmed' => false,
                    'fullTimeStudentConfirmed' => false,
                    'tuitionFeesPaidConfirmed' => true,
                    'accommodationFeesPaidConfirmed' => false,
                ],
            ],
        ])
        ->patch(route('v1.json.hms.hostel-applications.update', $application))
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.status', 'approved')
        ->assertJsonPath('data.attributes.paymentVerification.fullTimeStudentConfirmed', false);
});

test('json api hostel applications update can persist payment verification', function () {
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
        'status' => HostelApplicationStatusEnum::AWAITING_PAYMENT,
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
                'paymentVerification' => [
                    'addressOutsideCityCampusConfirmed' => true,
                    'fullTimeStudentConfirmed' => false,
                ],
            ],
        ])
        ->patch(route('v1.json.hms.hostel-applications.update', $application))
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.paymentVerification.addressOutsideCityCampusConfirmed', true)
        ->assertJsonPath('data.attributes.paymentVerification.fullTimeStudentConfirmed', false);

    expect($application->fresh()->status)->toBe(HostelApplicationStatusEnum::AWAITING_PAYMENT);
});

test('json api hostel applications cannot approve from pending status when requirements enabled', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('update:hostel-applications');
    Sanctum::actingAs($user);

    HmsSetting::resolveForTenant($tenant->id)->update([
        'require_full_time_study' => true,
        'require_tuition_paid' => true,
        'require_accommodation_paid' => true,
        'require_address_outside_campus' => true,
    ]);

    $student = createStudentForAllocationIndexTest();
    $room = ensureHostelRoomWithCapacity('Hostel D', 'APPROVE-PENDING');
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

    $this
        ->jsonApi('hostel-applications')
        ->withData([
            'type' => 'hostel-applications',
            'id' => (string) $application->id,
            'attributes' => [
                'status' => 'approved',
                'hostelRoomId' => $room->id,
                'paymentVerification' => [
                    'addressOutsideCityCampusConfirmed' => true,
                    'fullTimeStudentConfirmed' => true,
                    'tuitionFeesPaidConfirmed' => true,
                    'accommodationFeesPaidConfirmed' => true,
                ],
            ],
        ])
        ->patch(route('v1.json.hms.hostel-applications.update', $application))
        ->assertStatus(422);
});

test('json api hostel applications can approve from pending when direct allocation enabled', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('update:hostel-applications');
    Sanctum::actingAs($user);

    disableAllHmsApprovalRequirements($tenant->id);

    $student = createStudentForAllocationIndexTest();
    $room = ensureHostelRoomWithCapacity('Hostel D', 'APPROVE-PENDING-DIRECT');
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

    $this
        ->jsonApi('hostel-applications')
        ->withData([
            'type' => 'hostel-applications',
            'id' => (string) $application->id,
            'attributes' => [
                'status' => 'approved',
                'hostelRoomId' => $room->id,
            ],
        ])
        ->patch(route('v1.json.hms.hostel-applications.update', $application))
        ->assertSuccessful()
        ->assertJsonPath('data.attributes.status', 'approved');

    expect($application->fresh()->status)->toBe(HostelApplicationStatusEnum::APPROVED)
        ->and(HostelRoomAllocation::query()
            ->where('student_id', $student->id)
            ->where('hostel_room_id', $room->id)
            ->active()
            ->exists())->toBeTrue();
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
        'status' => HostelApplicationStatusEnum::AWAITING_PAYMENT,
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
        ->assertJsonPath('meta.hostels.0.name', 'Hostel D')
        ->assertJsonPath('meta.requiredPaymentVerification', fn ($value) => count($value) === 4);

    $roomsResponse = $this->getJson(route('v1.json.hostel-applications.approvalOptions', [
        'hostel_application' => $application->id,
        'hostelId' => $room->hostel_id,
    ]));

    $roomsResponse->assertSuccessful()
        ->assertJsonPath('meta.rooms.0.occupancyLabel', '1/2');
});

test('json api hostel applications approval rooms returns assignable rooms for hostel', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $student = createStudentForAllocationIndexTest();
    $room = ensureHostelRoomWithCapacity('Hostel D', 'APPROVE-ROOMS-01');
    $room->hostel->update(['type' => 'male']);

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
    ]));

    $this->getJson(route('v1.json.hostel-applications.approvalRooms', [
        'hostel_application' => $application->id,
        'hostelId' => $room->hostel_id,
    ]))
        ->assertSuccessful()
        ->assertJsonPath('meta.rooms.0.id', $room->id);
});

test('json api hostel applications pending queue returns pending and awaiting payment applications', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $student = createStudentForAllocationIndexTest();

    $current = HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'student_id' => $student->id,
        'gender_id' => $student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::AWAITING_PAYMENT,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $pendingStudent = createStudentForAllocationIndexTest();
    $pending = HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'student_id' => $pendingStudent->id,
        'gender_id' => $pendingStudent->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::PENDING,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $awaitingStudent = createStudentForAllocationIndexTest();
    $awaiting = HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'student_id' => $awaitingStudent->id,
        'gender_id' => $awaitingStudent->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::AWAITING_PAYMENT,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $approvedStudent = createStudentForAllocationIndexTest();
    HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'student_id' => $approvedStudent->id,
        'gender_id' => $approvedStudent->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::APPROVED,
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $response = $this->getJson(route('v1.json.hostel-applications.pendingQueue', [
        'exclude' => $current->id,
    ]))
        ->assertSuccessful();

    $ids = collect($response->json('meta.applications'))->pluck('id')->all();

    expect($ids)->toContain($pending->id, $awaiting->id)
        ->and($ids)->not->toContain($current->id);
});

test('json api hostel rooms index filters by hostel and available for application', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $student = createStudentForAllocationIndexTest();
    $room = ensureHostelRoomWithCapacity('Hostel D', 'JSONAPI-ROOM-01');
    $room->hostel->update(['type' => 'male']);

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
    ]));

    $this
        ->jsonApi('hostel-rooms')
        ->get(route('v1.json.hms.hostel-rooms.index', [
            'filter' => [
                'hostel' => (string) $room->hostel_id,
                'availableForApplication' => (string) $application->id,
            ],
        ]))
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', (string) $room->id);
});

test('json api hostel applications approval options blocked for pending when requirements enabled', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    HmsSetting::resolveForTenant($tenant->id)->update([
        'require_full_time_study' => true,
        'require_tuition_paid' => true,
        'require_accommodation_paid' => true,
        'require_address_outside_campus' => true,
    ]);

    $student = createStudentForAllocationIndexTest();
    ensureHostelRoomWithCapacity('Hostel D', 'OPTS-PENDING');

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

    $this->getJson(route('v1.json.hostel-applications.approvalOptions', [
        'hostel_application' => $application->id,
    ]))
        ->assertSuccessful()
        ->assertJsonPath('meta.canApprove', false)
        ->assertJsonPath('meta.blockers.0', 'not_awaiting_payment')
        ->assertJsonPath('meta.allowsDirectAllocation', false);
});

test('json api hostel applications approval options allow pending when direct allocation enabled', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    disableAllHmsApprovalRequirements($tenant->id);

    $student = createStudentForAllocationIndexTest();
    ensureHostelRoomWithCapacity('Hostel D', 'OPTS-PENDING-DIRECT')->hostel->update(['type' => 'male']);

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

    $this->getJson(route('v1.json.hostel-applications.approvalOptions', [
        'hostel_application' => $application->id,
    ]))
        ->assertSuccessful()
        ->assertJsonPath('meta.canApprove', true)
        ->assertJsonPath('meta.allowsDirectAllocation', true)
        ->assertJsonPath('meta.requiredPaymentVerification', [])
        ->assertJsonMissingPath('meta.blockers.0');
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

test('json api hostel applications student lookup blocks when student has active room allocation', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $studentProgram = createStudentReadyForHostelApplication('LOOKUP-ALLOC-ACTIVE');
    createRunningSemesterCalendar('2025/2026');
    $room = ensureHostelRoomWithCapacity('Hostel D', 'D-LOOKUP-ALLOC-ACTIVE');

    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $studentProgram->student_id,
        'type' => HostelAllocationTypeEnum::DIRECT,
        'status' => HostelAllocationStatusEnum::ACTIVE,
        'check_in' => now()->toDateString(),
        'check_out' => null,
    ]);

    $response = $this
        ->jsonApi()
        ->get(route('v1.json.hostel-applications.studentLookup', ['filter' => ['search' => 'LOOKUP-ALLOC-ACTIVE']]));

    $response->assertSuccessful()
        ->assertJsonPath('meta.canSubmit', false);

    expect($response->json('meta.blockers'))->toContain('student_already_allocated');
});

test('json api hostel applications student lookup blocks when student has pending room allocation', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $studentProgram = createStudentReadyForHostelApplication('LOOKUP-ALLOC-PENDING');
    createRunningSemesterCalendar('2025/2026');
    $room = ensureHostelRoomWithCapacity('Hostel D', 'D-LOOKUP-ALLOC-PENDING');

    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $studentProgram->student_id,
        'type' => HostelAllocationTypeEnum::DIRECT,
        'status' => HostelAllocationStatusEnum::PENDING,
        'check_in' => now()->toDateString(),
        'check_out' => null,
    ]);

    $response = $this
        ->jsonApi()
        ->get(route('v1.json.hostel-applications.studentLookup', ['filter' => ['search' => 'LOOKUP-ALLOC-PENDING']]));

    $response->assertSuccessful()
        ->assertJsonPath('meta.canSubmit', false);

    expect($response->json('meta.blockers'))->toContain('student_already_allocated');
});

test('json api hostel applications student lookup allows when student only has checked out allocation', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $studentProgram = createStudentReadyForHostelApplication('LOOKUP-ALLOC-CHECKED');
    createRunningSemesterCalendar('2025/2026');
    $room = ensureHostelRoomWithCapacity('Hostel D', 'D-LOOKUP-ALLOC-CHECKED');

    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $studentProgram->student_id,
        'type' => HostelAllocationTypeEnum::DIRECT,
        'status' => HostelAllocationStatusEnum::CHECKED_OUT,
        'check_in' => now()->subMonths(4)->toDateString(),
        'check_out' => now()->subMonth()->toDateString(),
    ]);

    $response = $this
        ->jsonApi()
        ->get(route('v1.json.hostel-applications.studentLookup', ['filter' => ['search' => 'LOOKUP-ALLOC-CHECKED']]));

    $response->assertSuccessful()
        ->assertJsonPath('meta.canSubmit', true);

    expect($response->json('meta.blockers'))->not->toContain('student_already_allocated');
});

test('json api hostel applications student lookup allows when student only has declined application', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    Sanctum::actingAs($user);

    $studentProgram = createStudentReadyForHostelApplication('LOOKUP-DECLINED');
    createRunningSemesterCalendar('2025/2026');
    ensureHostelRoomWithCapacity('Hostel D', 'D-LOOKUP-DECLINED');

    HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'student_id' => $studentProgram->student_id,
        'gender_id' => $studentProgram->student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::DECLINED,
        'decline_reason' => 'Not eligible',
        'next_of_kin_name' => 'Kin Name',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $response = $this
        ->jsonApi()
        ->get(route('v1.json.hostel-applications.studentLookup', ['filter' => ['search' => 'LOOKUP-DECLINED']]));

    $response->assertSuccessful()
        ->assertJsonPath('meta.canSubmit', true);

    expect($response->json('meta.blockers'))->not->toContain('pending_application_exists');
});

test('json api hostel applications store blocks student with open room allocation', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo('create:hostel-applications');
    Sanctum::actingAs($user);

    $studentProgram = createStudentReadyForHostelApplication('STORE-ALLOC');
    createRunningSemesterCalendar('2025/2026');
    $room = ensureHostelRoomWithCapacity('Hostel D', 'D-STORE-ALLOC');
    $enrolmentId = $studentProgram->student->fresh(['latestEnrolment'])->latestEnrolment?->id;

    HostelRoomAllocation::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'hostel_room_id' => $room->id,
        'student_id' => $studentProgram->student_id,
        'type' => HostelAllocationTypeEnum::DIRECT,
        'status' => HostelAllocationStatusEnum::ACTIVE,
        'check_in' => now()->toDateString(),
        'check_out' => null,
    ]);

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
