<?php

use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Rbac\RoleEnum;
use App\Enums\Shared\TenantEnum;
use App\Enums\Students\ApplicationTrackEnum;
use App\Models\Institution\Level;
use App\Models\Rbac\Role;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Users\User;
use App\Services\Enrollment\EnrollmentLookupService;
use App\Services\Students\OjetFormerStudentResolution;
use App\Services\Students\RegistrationIntentSession;
use App\Services\Students\ResolveOjetFormerStudentNumberService;
use Illuminate\Support\Facades\Hash;
use Mockery\MockInterface;

beforeEach(function () {
    Role::findOrCreate(RoleEnum::STUDENT->value, 'web');
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    Level::factory()->create(['show_on_current_application_period' => true]);
});

function ojetProgrammeSession(array $seeded): array
{
    return [
        RegistrationIntentSession::TRACK_KEY => ApplicationTrackEnum::Continuous->value,
        RegistrationIntentSession::CONTINUOUS_FOCUS_KEY => 'ojet',
        RegistrationIntentSession::LEVEL_KEY => $seeded['level']->id,
        RegistrationIntentSession::INTAKE_KEY => $seeded['continuousIntakeId'],
        RegistrationIntentSession::INSTRUCTIONS_KEY => true,
    ];
}

function createOjetPortalStudent(string $idNumber, string $studentNumber, ?string $passport = null): Student
{
    $tenantId = TenantEnum::HARARE_POLY->id();
    $title = Title::query()->firstOrCreate(['name' => 'Mr Ojet']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male Ojet']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single Ojet']);
    $idType = IdType::query()->firstOrCreate(['name' => 'National ID Ojet']);

    $user = User::factory()->create([
        'tenant_id' => $tenantId,
        'first_name' => 'Former',
        'last_name' => 'Student',
        'email' => 'former.ojet.'.uniqid().'@example.com',
        'password' => Hash::make('Password1!'),
        'email_verified_at' => now(),
    ]);
    $user->assignRole(RoleEnum::STUDENT);

    return Student::query()->create([
        'tenant_id' => $tenantId,
        'user_id' => $user->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'id_type_id' => $idType->id,
        'id_number' => $passport === null ? $idNumber : null,
        'passport_number' => $passport,
        'student_number' => $studentNumber,
        'date_of_birth' => '2000-01-01',
    ]);
}

test('ojet programme selection without identity is rejected', function () {
    $seeded = seedGuestContinuousProgramme('ojet');

    $this->withSession(ojetProgrammeSession($seeded))
        ->from(route('portal.register.programme'))
        ->post(route('portal.register.select-programme'), [
            'department_id' => $seeded['departmentId'],
            'department_level_id' => $seeded['departmentLevelId'],
            'course_id' => $seeded['courseId'],
            'mode_of_study_id' => $seeded['ojetModeId'],
            'identity_type' => 'zimbabwean',
        ])
        ->assertSessionHasErrors('id_number');

    expect(session(RegistrationIntentSession::READY_FOR_ACCOUNT_KEY))->toBeNull()
        ->and(session(RegistrationIntentSession::OJET_STUDENT_NUMBER_KEY))->toBeNull();
});

test('ojet programme selection with unknown identity is rejected and creates no account path', function () {
    $seeded = seedGuestContinuousProgramme('ojet');

    $this->mock(ResolveOjetFormerStudentNumberService::class, function (MockInterface $mock) {
        $mock->shouldReceive('resolve')
            ->once()
            ->andReturn(OjetFormerStudentResolution::unresolved());
        $mock->shouldReceive('normalizeIdentity')->never();
    });

    $this->withSession(ojetProgrammeSession($seeded))
        ->from(route('portal.register.programme'))
        ->post(route('portal.register.select-programme'), [
            'department_id' => $seeded['departmentId'],
            'department_level_id' => $seeded['departmentLevelId'],
            'course_id' => $seeded['courseId'],
            'mode_of_study_id' => $seeded['ojetModeId'],
            'identity_type' => 'zimbabwean',
            'id_number' => '63-1111111A63',
        ])
        ->assertSessionHasErrors('id_number');

    expect(session(RegistrationIntentSession::READY_FOR_ACCOUNT_KEY))->toBeNull()
        ->and(session(RegistrationIntentSession::OJET_STUDENT_NUMBER_KEY))->toBeNull();
});

test('ojet programme selection with csv national id becomes account ready', function () {
    $seeded = seedGuestContinuousProgramme('ojet');
    $idNumber = '63-2222222B63';
    $studentNumber = '25OJCSV01HP';

    $this->mock(ResolveOjetFormerStudentNumberService::class, function (MockInterface $mock) use ($idNumber, $studentNumber) {
        $mock->shouldReceive('resolve')
            ->once()
            ->andReturn(OjetFormerStudentResolution::fromLegacyNumber($studentNumber));
        $mock->shouldReceive('normalizeIdentity')
            ->once()
            ->with('zimbabwean', $idNumber)
            ->andReturn(EnrollmentLookupService::normalizeNationalId($idNumber));
    });

    $this->withSession(ojetProgrammeSession($seeded))
        ->post(route('portal.register.select-programme'), [
            'department_id' => $seeded['departmentId'],
            'department_level_id' => $seeded['departmentLevelId'],
            'course_id' => $seeded['courseId'],
            'mode_of_study_id' => $seeded['ojetModeId'],
            'identity_type' => 'zimbabwean',
            'id_number' => $idNumber,
        ])
        ->assertRedirect(route('portal.register.account'));

    expect(session(RegistrationIntentSession::READY_FOR_ACCOUNT_KEY))->toBeTrue()
        ->and(session(RegistrationIntentSession::OJET_STUDENT_NUMBER_KEY))->toBe($studentNumber)
        ->and(session(RegistrationIntentSession::OJET_ID_NUMBER_KEY))
        ->toBe(EnrollmentLookupService::normalizeNationalId($idNumber))
        ->and(session(RegistrationIntentSession::OJET_STUDENT_ID_KEY))->toBeNull();
});

test('ojet programme selection with csv passport becomes account ready', function () {
    $seeded = seedGuestContinuousProgramme('ojet');
    $passport = 'ZQ202780';
    $studentNumber = '25OJPAS01HP';

    $this->mock(ResolveOjetFormerStudentNumberService::class, function (MockInterface $mock) use ($passport, $studentNumber) {
        $mock->shouldReceive('resolve')
            ->once()
            ->andReturn(OjetFormerStudentResolution::fromLegacyNumber($studentNumber));
        $mock->shouldReceive('normalizeIdentity')
            ->once()
            ->with('international', $passport)
            ->andReturn(EnrollmentLookupService::normalizePassportNumber($passport));
    });

    $this->withSession(ojetProgrammeSession($seeded))
        ->post(route('portal.register.select-programme'), [
            'department_id' => $seeded['departmentId'],
            'department_level_id' => $seeded['departmentLevelId'],
            'course_id' => $seeded['courseId'],
            'mode_of_study_id' => $seeded['ojetModeId'],
            'identity_type' => 'international',
            'passport_number' => $passport,
        ])
        ->assertRedirect(route('portal.register.account'));

    expect(session(RegistrationIntentSession::READY_FOR_ACCOUNT_KEY))->toBeTrue()
        ->and(session(RegistrationIntentSession::OJET_STUDENT_NUMBER_KEY))->toBe($studentNumber)
        ->and(session(RegistrationIntentSession::OJET_PASSPORT_NUMBER_KEY))
        ->toBe(EnrollmentLookupService::normalizePassportNumber($passport));
});

test('ojet programme selection with existing portal student redirects to login', function () {
    $seeded = seedGuestContinuousProgramme('ojet');
    $idNumber = '63-3333333C63';
    $student = createOjetPortalStudent($idNumber, '25OJEXIST1HP');

    $this->mock(ResolveOjetFormerStudentNumberService::class, function (MockInterface $mock) use ($idNumber, $student) {
        $mock->shouldReceive('resolve')
            ->once()
            ->andReturn(OjetFormerStudentResolution::fromStudent($student, '25OJEXIST1HP'));
        $mock->shouldReceive('normalizeIdentity')
            ->once()
            ->andReturn(EnrollmentLookupService::normalizeNationalId($idNumber));
    });

    $this->withSession(ojetProgrammeSession($seeded))
        ->post(route('portal.register.select-programme'), [
            'department_id' => $seeded['departmentId'],
            'department_level_id' => $seeded['departmentLevelId'],
            'course_id' => $seeded['courseId'],
            'mode_of_study_id' => $seeded['ojetModeId'],
            'identity_type' => 'zimbabwean',
            'id_number' => $idNumber,
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHas('status');

    expect(session(RegistrationIntentSession::READY_FOR_ACCOUNT_KEY))->toBeNull()
        ->and(session(RegistrationIntentSession::OJET_STUDENT_NUMBER_KEY))->toBe('25OJEXIST1HP')
        ->and(session(RegistrationIntentSession::OJET_STUDENT_ID_KEY))->toBe($student->id);

    $this->assertDatabaseCount('students', 1);
});

test('ojet store refuses account creation without resolved student number', function () {
    $seeded = seedGuestContinuousProgramme('ojet');
    $email = 'ojet.unresolved.'.uniqid().'@example.com';

    $session = array_merge(ojetProgrammeSession($seeded), [
        RegistrationIntentSession::DEPARTMENT_KEY => $seeded['departmentId'],
        RegistrationIntentSession::DEPARTMENT_LEVEL_KEY => $seeded['departmentLevelId'],
        RegistrationIntentSession::COURSE_KEY => $seeded['courseId'],
        RegistrationIntentSession::MODE_KEY => $seeded['ojetModeId'],
        RegistrationIntentSession::READY_FOR_ACCOUNT_KEY => true,
    ]);

    $this->withSession($session)
        ->post(route('portal.store'), [
            'registration_path' => 'zimbabwean',
            'first_name' => 'No',
            'last_name' => 'Number',
            'email' => $email,
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'id_number' => '63-4444444D63',
            'acknowledged_advert' => true,
        ])
        ->assertRedirect(route('portal.register.track'));

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => $email]);
});

test('ojet store creates account with stamped student number from csv resolve', function () {
    $seeded = seedGuestContinuousProgramme('ojet');
    $idNumber = '63-5555555E63';
    $studentNumber = '25OJNEW01HP';
    $email = 'ojet.csv.'.uniqid().'@example.com';

    $session = array_merge(ojetProgrammeSession($seeded), [
        RegistrationIntentSession::DEPARTMENT_KEY => $seeded['departmentId'],
        RegistrationIntentSession::DEPARTMENT_LEVEL_KEY => $seeded['departmentLevelId'],
        RegistrationIntentSession::COURSE_KEY => $seeded['courseId'],
        RegistrationIntentSession::MODE_KEY => $seeded['ojetModeId'],
        RegistrationIntentSession::READY_FOR_ACCOUNT_KEY => true,
        RegistrationIntentSession::OJET_IDENTITY_TYPE_KEY => 'zimbabwean',
        RegistrationIntentSession::OJET_ID_NUMBER_KEY => EnrollmentLookupService::normalizeNationalId($idNumber),
        RegistrationIntentSession::OJET_STUDENT_NUMBER_KEY => $studentNumber,
    ]);

    $this->withSession($session)
        ->post(route('portal.store'), [
            'registration_path' => 'zimbabwean',
            'first_name' => 'Csv',
            'last_name' => 'Former',
            'email' => $email,
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'id_number' => $idNumber,
            'acknowledged_advert' => true,
        ])
        ->assertRedirect();

    $this->assertAuthenticated();
    expect(session('registration.ojet_student_number'))->toBe($studentNumber);
});

test('non ojet programme selection still succeeds without identity', function () {
    $seeded = seedGuestRegistrationProgramme();

    $this->withSession([
        RegistrationIntentSession::TRACK_KEY => ApplicationTrackEnum::Regular->value,
        RegistrationIntentSession::LEVEL_KEY => $seeded['level']->id,
        RegistrationIntentSession::INTAKE_KEY => $seeded['intakeId'],
    ])->post(route('portal.register.select-programme'), [
        'department_id' => $seeded['departmentId'],
        'department_level_id' => $seeded['departmentLevelId'],
        'course_id' => $seeded['courseId'],
        'mode_of_study_id' => $seeded['modeId'],
    ])->assertRedirect(route('portal.register.account'));

    expect(session(RegistrationIntentSession::READY_FOR_ACCOUNT_KEY))->toBeTrue()
        ->and(session(RegistrationIntentSession::OJET_STUDENT_NUMBER_KEY))->toBeNull();
});

test('resolve service treats csv missing sentinel as unresolved', function () {
    $service = app(ResolveOjetFormerStudentNumberService::class);

    expect($service->lookupLegacyStudentNumber('__definitely_missing_identity__'))->toBeNull();
});

test('resolve service finds existing student by national id', function () {
    $idNumber = '63-6666666F63';
    $student = createOjetPortalStudent($idNumber, '25OJRES01HP');

    $resolution = app(ResolveOjetFormerStudentNumberService::class)
        ->resolve('zimbabwean', $idNumber);

    expect($resolution->resolved)->toBeTrue()
        ->and($resolution->studentNumber)->toBe('25OJRES01HP')
        ->and($resolution->student?->id)->toBe($student->id)
        ->and($resolution->isOnCurrentPortal())->toBeTrue();
});
