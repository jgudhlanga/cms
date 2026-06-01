<?php

use App\Enums\HMS\HostelNoticeStatusEnum;
use App\Enums\HMS\HostelNoticeTypeEnum;
use App\Enums\HMS\HostelQueryStatusEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\HMS\Hostel;
use App\Models\HMS\HostelNotice;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Laravel\Sanctum\Sanctum;

test('json api portal student can create and list hostel queries', function () {
    $tenant = Tenant::query()->firstOrFail();
    $portalUser = User::factory()->create(['tenant_id' => $tenant->id]);
    $portalUser->givePermissionTo('manageOwnStudentAccommodationDetails:students');
    Sanctum::actingAs($portalUser);

    $studentProgram = createStudentReadyForHostelApplication('QUERY-STU-01');
    $student = $studentProgram->student;
    $student->update(['user_id' => $portalUser->id]);

    $this
        ->jsonApi('hostel-queries')
        ->withData([
            'type' => 'hostel-queries',
            'attributes' => [
                'studentId' => $student->id,
                'category' => 'plumbing',
                'subject' => 'Leaking tap',
                'description' => 'Bathroom tap leaking',
                'priority' => 'high',
            ],
        ])
        ->post(route('v1.json.hms.hostel-queries.store'))
        ->assertCreated();

    $this
        ->jsonApi('hostel-queries')
        ->filter(['student' => (string) $student->id])
        ->get(route('v1.json.hms.hostel-queries.index'))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.attributes.subject', 'Leaking tap')
        ->assertJsonPath('data.0.attributes.status', HostelQueryStatusEnum::OPEN->value);
});

test('json api portal student can create hostel leave request', function () {
    $tenant = Tenant::query()->firstOrFail();
    $portalUser = User::factory()->create(['tenant_id' => $tenant->id]);
    $portalUser->givePermissionTo('manageOwnStudentAccommodationDetails:students');
    Sanctum::actingAs($portalUser);

    $studentProgram = createStudentReadyForHostelApplication('LEAVE-STU-01');
    $student = $studentProgram->student;
    $student->update(['user_id' => $portalUser->id]);

    $this
        ->jsonApi('hostel-leaves')
        ->withData([
            'type' => 'hostel-leaves',
            'attributes' => [
                'studentId' => $student->id,
                'leaveType' => 'Home Visit',
                'fromDate' => now()->addWeek()->toDateString(),
                'toDate' => now()->addWeeks(2)->toDateString(),
                'reason' => 'Family visit',
            ],
        ])
        ->post(route('v1.json.hms.hostel-leaves.store'))
        ->assertCreated();
});

test('json api staff can create notice with audience and student sees published notice', function () {
    $tenant = Tenant::query()->firstOrFail();
    $staff = User::factory()->create(['tenant_id' => $tenant->id]);
    $staff->givePermissionTo('create:hostel-notices', 'viewAny:hostel-notices');
    Sanctum::actingAs($staff);

    $studentProgram = createStudentReadyForHostelApplication('NOTICE-STU-01');
    $student = $studentProgram->student;

    $hostel = Hostel::query()->firstOrCreate(
        ['name' => 'Hostel D'],
        [
            'tenant_id' => TenantEnum::HARARE_POLY->id(),
            'floor_count' => 1,
            'rooms_count' => 1,
            'capacity' => 2,
            'status' => 'active',
            'type' => null,
            'location' => null,
            'warden_id' => null,
            'description' => null,
        ],
    );

    $response = $this
        ->jsonApi('hostel-notices')
        ->withData([
            'type' => 'hostel-notices',
            'attributes' => [
                'title' => 'Water interruption',
                'content' => 'Maintenance on Sunday',
                'noticeType' => HostelNoticeTypeEnum::URGENT->value,
                'status' => HostelNoticeStatusEnum::PUBLISHED->value,
                'isUrgent' => true,
                'audienceHostelIds' => [$hostel->id],
                'audienceFloors' => [],
                'audienceStudentIds' => [$student->id],
            ],
        ])
        ->post(route('v1.json.hms.hostel-notices.store'));

    $response->assertCreated();

    $portalUser = User::factory()->create(['tenant_id' => $tenant->id]);
    $portalUser->givePermissionTo('manageOwnStudentAccommodationDetails:students');
    $student->update(['user_id' => $portalUser->id]);
    Sanctum::actingAs($portalUser);

    $this
        ->jsonApi('hostel-notices')
        ->filter(['student' => (string) $student->id])
        ->get(route('v1.json.hms.hostel-notices.index'))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.attributes.title', 'Water interruption')
        ->assertJsonPath('data.0.attributes.status', HostelNoticeStatusEnum::PUBLISHED->value);
});

test('json api broadcast notice visible to all students', function () {
    $tenant = Tenant::query()->firstOrFail();
    $staff = User::factory()->create(['tenant_id' => $tenant->id]);
    $staff->givePermissionTo('create:hostel-notices', 'viewAny:hostel-notices');
    Sanctum::actingAs($staff);

    HostelNotice::withoutEvents(fn () => HostelNotice::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'posted_by' => $staff->id,
        'title' => 'General announcement',
        'content' => 'Welcome back',
        'type' => HostelNoticeTypeEnum::GENERAL,
        'status' => HostelNoticeStatusEnum::PUBLISHED,
        'is_urgent' => false,
        'published_at' => now(),
    ]));

    $studentProgram = createStudentReadyForHostelApplication('NOTICE-ALL-01');
    $portalUser = User::factory()->create(['tenant_id' => $tenant->id]);
    $portalUser->givePermissionTo('manageOwnStudentAccommodationDetails:students');
    $studentProgram->student->update(['user_id' => $portalUser->id]);
    Sanctum::actingAs($portalUser);

    $this
        ->jsonApi('hostel-notices')
        ->filter(['student' => (string) $studentProgram->student_id])
        ->get(route('v1.json.hms.hostel-notices.index'))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');
});
