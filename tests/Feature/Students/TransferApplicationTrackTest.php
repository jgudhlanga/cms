<?php

use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Rbac\RoleEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Enums\Students\ApplicationTrackEnum;
use App\Models\Institution\IntakePeriod;
use App\Models\Rbac\Role;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Relationship;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Students\StudentTransfer;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Services\Students\ApplicationEligibilityService;
use App\Services\Students\ApplicationTrackSession;
use App\Services\Students\RegistrationAvailabilityService;
use App\Services\Students\RegistrationIntentSession;

beforeEach(function () {
    Role::findOrCreate(RoleEnum::STUDENT->name(), 'web');
});

function createTransferTrackApplicant(): User
{
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email_verified_at' => now(),
    ]);
    $user->assignRole(RoleEnum::STUDENT->name());
    $user->givePermissionTo('manageOwnStudentPersonalDetails:students');

    return $user;
}

function enableTransferPathOnIntake(IntakePeriod $intake): IntakePeriod
{
    $intake->update(['show_transfer_path' => true]);

    return $intake->refresh();
}

test('transfer track is available when open regular intake has show transfer path', function () {
    $intake = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    enableTransferPathOnIntake($intake);

    expect(app(RegistrationAvailabilityService::class)->isTransferRegistrationOpen())->toBeTrue()
        ->and(app(ApplicationEligibilityService::class)->availableTracks())
        ->toContain(ApplicationTrackEnum::Transfer);

    $user = createTransferTrackApplicant();

    $this->actingAs($user)
        ->post(route('portal.application.select-track'), ['track' => ApplicationTrackEnum::Transfer->value])
        ->assertRedirect(route('portal.application.transfer-college'));

    expect(session('application.track'))->toBe(ApplicationTrackEnum::Transfer->value);
});

test('transfer is not offered when open regular intake hides transfer path', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);

    expect(app(RegistrationAvailabilityService::class)->isTransferRegistrationOpen())->toBeFalse()
        ->and(app(ApplicationEligibilityService::class)->availableTracks())
        ->not->toContain(ApplicationTrackEnum::Transfer);
});

test('transfer is not offered when only continuous intake is open without transfer path', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Closed->value);
    ensureContinuousIntakeOpen();

    expect(app(ApplicationEligibilityService::class)->availableTracks())
        ->not->toContain(ApplicationTrackEnum::Transfer)
        ->toContain(ApplicationTrackEnum::Continuous);
});

test('transfer is offered when open continuous intake has show transfer path', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Closed->value);
    $continuous = ensureContinuousIntakeOpen();
    enableTransferPathOnIntake($continuous);

    expect(app(RegistrationAvailabilityService::class)->isTransferRegistrationOpen())->toBeTrue()
        ->and(app(ApplicationEligibilityService::class)->availableTracks())
        ->toContain(ApplicationTrackEnum::Transfer)
        ->toContain(ApplicationTrackEnum::Continuous);
});

test('transfer level options redirect to college when college name is missing', function () {
    enableTransferPathOnIntake(ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value));
    $user = createTransferTrackApplicant();

    $this->actingAs($user)
        ->withSession(['application.track' => ApplicationTrackEnum::Transfer->value])
        ->get(route('portal.application.level-options'))
        ->assertRedirect(route('portal.application.transfer-college'));
});

test('transfer college form stores college name and continues to level options', function () {
    enableTransferPathOnIntake(ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value));
    $user = createTransferTrackApplicant();

    $this->actingAs($user)
        ->withSession(['application.track' => ApplicationTrackEnum::Transfer->value])
        ->post(route('portal.application.transfer-college.store'), [
            'college_name' => 'Mutare Polytechnic',
        ])
        ->assertRedirect(route('portal.application.level-options'));

    expect(session(ApplicationTrackSession::TRANSFER_COLLEGE_NAME_KEY))->toBe('Mutare Polytechnic');
});

test('guest transfer track redirects to college step', function () {
    enableTransferPathOnIntake(ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value));

    $this->post(route('portal.register.select-track'), [
        'track' => ApplicationTrackEnum::Transfer->value,
    ])->assertRedirect(route('portal.register.college'));

    expect(session(RegistrationIntentSession::TRACK_KEY))->toBe(ApplicationTrackEnum::Transfer->value);
});

test('guest transfer college form stores college and continues to level', function () {
    enableTransferPathOnIntake(ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value));

    $this->withSession([
        RegistrationIntentSession::TRACK_KEY => ApplicationTrackEnum::Transfer->value,
    ])
        ->post(route('portal.register.select-college'), [
            'college_name' => 'Gweru Polytechnic',
        ])
        ->assertRedirect(route('portal.register.level'));

    expect(session(RegistrationIntentSession::TRANSFER_COLLEGE_NAME_KEY))->toBe('Gweru Polytechnic');
});

test('guest transfer promote copies college name into application session', function () {
    $intent = app(RegistrationIntentSession::class);
    $trackSession = app(ApplicationTrackSession::class);

    $intent->setTrack(ApplicationTrackEnum::Transfer);
    $intent->setTransferCollegeName('Bulawayo Polytechnic');

    $intent->promoteToApplicationSession($trackSession);

    expect($trackSession->get())->toBe(ApplicationTrackEnum::Transfer)
        ->and($trackSession->transferCollegeName())->toBe('Bulawayo Polytechnic');
});

test('transfer application submit creates student transfer record', function () {
    $seeded = seedGuestRegistrationProgramme();
    $seeded['level']->update(['has_application_fee_payment' => false]);
    enableTransferPathOnIntake(IntakePeriod::query()->findOrFail($seeded['intakeId']));

    $user = createTransferTrackApplicant();

    $title = Title::query()->firstOrCreate(['name' => 'Mr Transfer Track']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male Transfer Track']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single Transfer Track']);
    $relationship = Relationship::query()->firstOrCreate(['name' => 'Guardian Transfer Track']);
    $idType = IdType::query()->firstOrCreate(
        ['id' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id()],
        ['name' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->value],
    );

    $idNumber = '63-7654321B63';

    $this->actingAs($user)
        ->withSession([
            'application.track' => ApplicationTrackEnum::Transfer->value,
            'application.intake_period_id' => $seeded['intakeId'],
            'application.level_id' => $seeded['level']->id,
            'application.department_id' => $seeded['departmentId'],
            'application.department_level_id' => $seeded['departmentLevelId'],
            'application.course_id' => $seeded['courseId'],
            'application.mode_of_study_id' => $seeded['modeId'],
            ApplicationTrackSession::TRANSFER_COLLEGE_NAME_KEY => 'Kwekwe Polytechnic',
            'registration.id_number' => $idNumber,
            'registration.id_type_id' => $idType->id,
        ])
        ->post(route('portal.store-application'), [
            'first_name' => 'Trans',
            'last_name' => 'Fer',
            'gender_id' => $gender->id,
            'marital_status_id' => $maritalStatus->id,
            'title_id' => $title->id,
            'mode_of_study_id' => $seeded['modeId'],
            'id_type_id' => $idType->id,
            'id_number' => $idNumber,
            'date_of_birth' => '2001-02-02',
            'address_1' => 'Address 1',
            'address_2' => 'Address 2',
            'address_3' => 'Address 3',
            'email' => $user->email,
            'phone_number' => '0777222222',
            'next_of_kin_name' => 'Kin Name',
            'next_of_kin_address_1' => 'Kin 1',
            'next_of_kin_address_2' => 'Kin 2',
            'next_of_kin_address_3' => 'Kin 3',
            'relationship_id' => $relationship->id,
            'next_of_kin_phone_number' => '0777333333',
            'department_id' => $seeded['departmentId'],
            'level_id' => $seeded['departmentLevelId'],
            'course_id' => $seeded['courseId'],
            'disability_status' => 'no',
            'college_name' => 'Kwekwe Polytechnic',
        ])
        ->assertRedirect(route('portal.applications'));

    $student = Student::query()->where('user_id', $user->id)->first();
    expect($student)->not->toBeNull();

    $application = $student->applications()->latest()->first();
    expect($application)->not->toBeNull();

    $transfer = StudentTransfer::query()
        ->where('student_id', $student->id)
        ->where('student_application_id', $application->id)
        ->first();

    expect($transfer)->not->toBeNull()
        ->and($transfer->college_name)->toBe('Kwekwe Polytechnic');

    expect(session('application.track'))->toBeNull()
        ->and(session(ApplicationTrackSession::TRANSFER_COLLEGE_NAME_KEY))->toBeNull();
});

test('confirm application page receives transfer college name', function () {
    enableTransferPathOnIntake(ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value));
    $user = createTransferTrackApplicant();

    $this->actingAs($user)
        ->withSession([
            'application.track' => ApplicationTrackEnum::Transfer->value,
            ApplicationTrackSession::TRANSFER_COLLEGE_NAME_KEY => 'Masvingo Polytechnic',
        ])
        ->get(route('portal.application.confirm'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/application/ConfirmApplication')
            ->where('applicationTrack', ApplicationTrackEnum::Transfer->value)
            ->where('transferCollegeName', 'Masvingo Polytechnic')
        );
});
