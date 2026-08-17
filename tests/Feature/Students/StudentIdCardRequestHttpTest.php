<?php

declare(strict_types=1);

use App\Enums\Shared\ModuleEnum;
use App\Enums\Students\IdCardRequestReasonEnum;
use App\Enums\Students\IdCardRequestStatusEnum;
use App\Helpers\PermissionHelper;
use App\Models\Rbac\Module;
use App\Models\Rbac\Permission;
use App\Models\Students\StudentIdCardSetting;
use App\Services\Students\StudentIdCardRequestService;
use App\Support\Rbac\PermissionRegistry;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;

test('student portal shows the submit form before a photo is uploaded', function () {
    ['user' => $user] = createIdCardStudent();
    grantIdCardPortalPermission($user);

    $this->actingAs($user)
        ->get(route('portal.id-card.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/student/id-card/Index')
            ->where('canSubmit', true)
            ->where('hasPhoto', false)
            ->where('hasStudentNumber', true)
        );
});

test('student can upload an id photo and submit a new request', function () {
    ['user' => $user, 'student' => $student] = createIdCardStudent();
    grantIdCardPortalPermission($user);

    $this->actingAs($user)
        ->post(route('portal.id-card.photo'), [
            'photo' => UploadedFile::fake()->image('id-photo.jpg', 400, 500),
        ])
        ->assertRedirect();

    expect($student->fresh()->latestIdPhoto())->not->toBeNull();

    $this->actingAs($user)
        ->post(route('portal.id-card.store'), [
            'reason' => IdCardRequestReasonEnum::NEW->value,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('student_id_card_requests', [
        'student_id' => $student->id,
        'reason' => IdCardRequestReasonEnum::NEW->value,
        'status' => IdCardRequestStatusEnum::PENDING->value,
    ]);
});

test('student cannot open the admin id card queue', function () {
    ['user' => $user] = createIdCardStudent();
    grantIdCardPortalPermission($user);

    $this->actingAs($user)
        ->get(route('admin.students.id-card-requests.index'))
        ->assertForbidden();
});

test('staff without viewAny cannot open the admin id card queue', function () {
    ['tenant' => $tenant] = createIdCardStudent();
    $staff = createIdCardStaff((int) $tenant->id, []);

    $this->actingAs($staff)
        ->get(route('admin.students.id-card-requests.index'))
        ->assertForbidden();
});

test('staff with viewAny can open the admin id card queue', function () {
    ['tenant' => $tenant] = createIdCardStudent();
    $staff = createIdCardStaff((int) $tenant->id);

    $this->actingAs($staff)
        ->get(route('admin.students.id-card-requests.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('students/id-card-requests/Index')
            ->where('statusOptions.0.value', IdCardRequestStatusEnum::AWAITING_PAYMENT->value)
            ->where('statusOptions.0.label', __('students.id_card_status_awaiting_payment'))
            ->where('reasonOptions.0.value', IdCardRequestReasonEnum::NEW->value)
            ->where('reasonOptions.0.label', __('students.id_card_reason_new'))
            ->has('idCardSettings.institutionName')
            ->where('canBulkPrint', false)
        );
});

test('admin queue enables bulk print when approved cards are ready', function () {
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    $service = app(StudentIdCardRequestService::class);
    $staff = createIdCardStaff((int) $tenant->id, [
        'viewAny:student-id-card-requests',
        'review:student-id-card-requests',
    ]);
    $service->approve($service->submit($student, IdCardRequestReasonEnum::NEW), $staff);

    $this->actingAs($staff)
        ->get(route('admin.students.id-card-requests.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('students/id-card-requests/Index')
            ->where('canBulkPrint', true)
        );
});

test('json api id card index requires viewAny', function () {
    ['tenant' => $tenant] = createIdCardStudent();
    $staff = createIdCardStaff((int) $tenant->id, []);
    Sanctum::actingAs($staff);

    $this->jsonApi('student-id-card-requests')
        ->get(route('v1.json.students.student-id-card-requests.index'))
        ->assertForbidden();
});

test('json api id card index returns requests for permitted staff', function () {
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    $request = app(StudentIdCardRequestService::class)->submit($student, IdCardRequestReasonEnum::NEW);
    $staff = createIdCardStaff((int) $tenant->id);
    Sanctum::actingAs($staff);

    $this->jsonApi('student-id-card-requests')
        ->get(route('v1.json.students.student-id-card-requests.index'))
        ->assertSuccessful()
        ->assertJsonPath('data.0.id', (string) $request->id);
});

test('review print and issue each require their own permission', function () {
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    $service = app(StudentIdCardRequestService::class);
    $pending = $service->submit($student, IdCardRequestReasonEnum::NEW);

    $viewer = createIdCardStaff((int) $tenant->id, [
        'viewAny:student-id-card-requests',
        'view:student-id-card-requests',
    ]);

    $this->actingAs($viewer)
        ->post(route('admin.students.id-card-requests.approve', $pending))
        ->assertForbidden();

    $this->actingAs($viewer)
        ->post(route('admin.students.id-card-requests.reject', $pending), [
            'rejection_reason' => 'Photo is not acceptable.',
        ])
        ->assertForbidden();

    $reviewer = createIdCardStaff((int) $tenant->id, ['review:student-id-card-requests']);
    $this->actingAs($reviewer)
        ->post(route('admin.students.id-card-requests.approve', $pending))
        ->assertRedirect();

    $approved = $pending->fresh();
    expect($approved->status)->toBe(IdCardRequestStatusEnum::APPROVED);

    $this->actingAs($viewer)
        ->get(route('admin.students.id-card-requests.print', $approved))
        ->assertForbidden();

    $printer = createIdCardStaff((int) $tenant->id, ['print:student-id-card-requests']);
    $this->actingAs($printer)
        ->get(route('admin.students.id-card-requests.print', $approved))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $printed = $approved->fresh();
    expect($printed->status)->toBe(IdCardRequestStatusEnum::PRINTED);

    $this->actingAs($viewer)
        ->post(route('admin.students.id-card-requests.issue', $printed))
        ->assertForbidden();

    $issuer = createIdCardStaff((int) $tenant->id, ['issue:student-id-card-requests']);
    $this->actingAs($issuer)
        ->post(route('admin.students.id-card-requests.issue', $printed))
        ->assertRedirect();

    expect($printed->fresh()->status)->toBe(IdCardRequestStatusEnum::ISSUED);
});

test('permission registry resolves student ids module title', function () {
    expect(PermissionRegistry::moduleTitleForGroupKey('student-ids'))->toBe('Student IDs');
});

test('student ids permissions belong to the student ids module', function () {
    $module = Module::query()->where('slug', ModuleEnum::STUDENT_IDS->slug())->first();

    expect($module)->not->toBeNull()
        ->and($module->title)->toBe(ModuleEnum::STUDENT_IDS->value)
        ->and((bool) $module->status)->toBeTrue();

    $moduleIds = Permission::query()
        ->whereIn('name', PermissionHelper::idCardAdminPermissions())
        ->pluck('module_id')
        ->unique()
        ->all();

    expect($moduleIds)->toBe([$module->id]);
});

test('staff cannot open the admin id card queue when the student ids module is disabled', function () {
    ['tenant' => $tenant] = createIdCardStudent();
    $staff = createIdCardStaff((int) $tenant->id);
    disableStudentIdsModule();

    $this->actingAs($staff)
        ->get(route('admin.students.id-card-requests.index'))
        ->assertForbidden();
});

test('student cannot open the id card portal when the student ids module is disabled', function () {
    ['user' => $user] = createIdCardStudent();
    grantIdCardPortalPermission($user);
    disableStudentIdsModule();

    $this->actingAs($user)
        ->get(route('portal.id-card.index'))
        ->assertForbidden();
});

test('staff cannot update id card settings without permission', function () {
    ['tenant' => $tenant] = createIdCardStudent();
    $staff = createIdCardStaff((int) $tenant->id);

    $this->actingAs($staff)
        ->post(route('admin.students.id-card-requests.settings.update'), [
            'institution_name' => 'Test College',
            'website' => 'www.test.ac.zw',
            'return_name' => 'Test College',
            'return_address' => 'P.O. Box 1, Harare',
            'return_phone' => '0123',
        ])
        ->assertForbidden();
});

test('staff can save id card settings with logo and signature', function () {
    ['tenant' => $tenant] = createIdCardStudent();
    $staff = createIdCardStaff((int) $tenant->id, [
        'viewAny:student-id-card-requests',
        'update:student-id-card-settings',
    ]);

    $this->actingAs($staff)
        ->post(route('admin.students.id-card-requests.settings.update'), [
            'institution_name' => 'Test College',
            'website' => 'www.test.ac.zw',
            'return_name' => 'Test College Registry',
            'return_address' => 'P.O. Box 1, Harare',
            'return_phone' => '0123 456',
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
            'principal_signature' => UploadedFile::fake()->image('signature.png', 300, 80),
        ])
        ->assertRedirect();

    $settings = StudentIdCardSetting::resolveForTenant((int) $tenant->id);

    expect($settings->institution_name)->toBe('Test College')
        ->and($settings->website)->toBe('www.test.ac.zw')
        ->and($settings->return_name)->toBe('Test College Registry')
        ->and($settings->return_address)->toBe('P.O. Box 1, Harare')
        ->and($settings->return_phone)->toBe('0123 456')
        ->and($settings->getFirstMedia(StudentIdCardSetting::LOGO_COLLECTION))->not->toBeNull()
        ->and($settings->getFirstMedia(StudentIdCardSetting::SIGNATURE_COLLECTION))->not->toBeNull();
});

test('staff cannot bulk print without print permission', function () {
    ['tenant' => $tenant] = createIdCardStudent();
    $staff = createIdCardStaff((int) $tenant->id);

    $this->actingAs($staff)
        ->get(route('admin.students.id-card-requests.bulk-print'))
        ->assertForbidden();
});

test('bulk print redirects when there are no approved cards', function () {
    ['tenant' => $tenant] = createIdCardStudent();
    $staff = createIdCardStaff((int) $tenant->id, ['print:student-id-card-requests']);

    $this->actingAs($staff)
        ->get(route('admin.students.id-card-requests.bulk-print'))
        ->assertRedirect(route('admin.students.id-card-requests.index'));
});

test('bulk print exports approved cards without changing status', function () {
    ['tenant' => $tenant, 'student' => $student] = createIdCardStudent();
    attachIdCardPhoto($student);
    $service = app(StudentIdCardRequestService::class);
    $admin = createIdCardStaff((int) $tenant->id, [
        'review:student-id-card-requests',
        'print:student-id-card-requests',
    ]);

    $approved = $service->approve($service->submit($student, IdCardRequestReasonEnum::NEW), $admin);

    $this->actingAs($admin)
        ->get(route('admin.students.id-card-requests.bulk-print'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $fresh = $approved->fresh();

    expect($fresh->status)->toBe(IdCardRequestStatusEnum::APPROVED)
        ->and($fresh->serial_number)->not->toBeNull()
        ->and($fresh->printed_at)->toBeNull();
});
