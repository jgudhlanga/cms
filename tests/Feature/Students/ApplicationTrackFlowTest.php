<?php

use App\Enums\Rbac\RoleEnum;
use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Institution\LevelEnum;
use App\Enums\Institution\ModeOfStudyEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Enums\Students\ApplicationTrackEnum;
use App\Models\Rbac\Role;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Relationship;
use App\Models\Shared\Title;
use App\Models\Students\ApplicationFee;
use App\Models\Students\Student;
use App\Models\Students\StudentApprentice;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Services\Students\ApplicationEligibilityService;
use App\Services\Students\ApplicationFeeService;
use App\Services\Students\RegistrationAvailabilityService;

beforeEach(function () {
    Role::findOrCreate(RoleEnum::STUDENT->name(), 'web');
});

function createTrackApplicant(): User
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

test('regular closed continuous open allows continuous track and blocks regular', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Closed->value);
    $continuous = ensureContinuousIntakeOpen();

    $availability = app(RegistrationAvailabilityService::class);

    expect($availability->isRegularRegistrationOpen())->toBeFalse()
        ->and($availability->isContinuousRegistrationOpen())->toBeTrue()
        ->and($availability->isAnyRegistrationOpen())->toBeTrue();

    $user = createTrackApplicant();

    $this->actingAs($user)
        ->post(route('portal.application.select-track'), ['track' => ApplicationTrackEnum::Continuous->value])
        ->assertRedirect(route('portal.application.level-options'));

    expect(session('application.track'))->toBe(ApplicationTrackEnum::Continuous->value)
        ->and(session('application.intake_period_id'))->toBe($continuous->id);

    $this->actingAs($user)
        ->withSession(['application.track' => ApplicationTrackEnum::Regular->value])
        ->get(route('portal.application.level-options'))
        ->assertRedirect(route('portal.application.track'));
});

test('continuous eligibility requires sdp level or ojet mode', function () {
    $eligibility = app(ApplicationEligibilityService::class);

    $sdp = Level::query()->firstOrCreate(
        ['name' => LevelEnum::SDP->value],
        ['description' => 'SDP', 'position' => 9, 'show_on_current_application_period' => true],
    );

    $nc = Level::query()->firstOrCreate(
        ['name' => LevelEnum::NC->value],
        ['description' => 'NC', 'position' => 5, 'show_on_current_application_period' => true],
    );

    $ojet = ModeOfStudy::query()->firstOrCreate(
        ['name' => ModeOfStudyEnum::OJET->value],
        ['description' => 'Ojet'],
    );

    $fullTime = ModeOfStudy::query()->firstOrCreate(
        ['name' => ModeOfStudyEnum::FULL_TIME->value],
        ['description' => 'Full Time'],
    );

    expect($eligibility->isContinuousEligible($sdp, $fullTime))->toBeTrue()
        ->and($eligibility->isContinuousEligible($nc, $ojet))->toBeTrue()
        ->and($eligibility->isContinuousEligible($nc, $fullTime))->toBeFalse();
});

test('apprentice track is available only when regular intake is open', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);

    expect(app(RegistrationAvailabilityService::class)->isApprenticeRegistrationOpen())->toBeTrue();

    $user = createTrackApplicant();

    $this->actingAs($user)
        ->post(route('portal.application.select-track'), ['track' => ApplicationTrackEnum::Apprentice->value])
        ->assertRedirect(route('portal.application.level-options'));

    expect(session('application.track'))->toBe(ApplicationTrackEnum::Apprentice->value);

    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Closed->value);
    ensureContinuousIntakeOpen();

    $this->actingAs($user)
        ->withSession(['application.track' => ApplicationTrackEnum::Apprentice->value])
        ->get(route('portal.application.apprentice'))
        ->assertRedirect(route('portal.application.track'));
});

test('apprentice is not offered when only continuous intake is open', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Closed->value);
    ensureContinuousIntakeOpen();

    expect(app(ApplicationEligibilityService::class)->availableTracks())
        ->not->toContain(ApplicationTrackEnum::Apprentice)
        ->toContain(ApplicationTrackEnum::Continuous);
});

test('continuous level filtering excludes non-ojet levels without ojet offerings', function () {
    $eligibility = app(ApplicationEligibilityService::class);

    $sdp = Level::query()->firstOrCreate(
        ['name' => LevelEnum::SDP->value],
        ['description' => 'SDP', 'position' => 9, 'show_on_current_application_period' => true],
    );

    $nc = Level::query()->firstOrCreate(
        ['name' => LevelEnum::NC->value],
        ['description' => 'NC', 'position' => 5, 'show_on_current_application_period' => true],
    );

    $filtered = $eligibility->filterLevelsForContinuousTrack(collect([$sdp, $nc]));

    expect($filtered->pluck('id')->all())->toContain($sdp->id);

    if ($eligibility->isLevelEligibleForContinuous($nc)) {
        expect($filtered->pluck('id')->all())->toContain($nc->id);
    } else {
        expect($filtered->pluck('id')->all())->not->toContain($nc->id);
    }
});

test('express apprentice application creates student apprentice record without application fee', function () {
    $seeded = seedGuestRegistrationProgramme();
    $user = createTrackApplicant();

    $title = Title::query()->firstOrCreate(['name' => 'Mr Apprentice Track']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male Apprentice Track']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single Apprentice Track']);
    $relationship = Relationship::query()->firstOrCreate(['name' => 'Guardian Apprentice Track']);
    $idType = IdType::query()->firstOrCreate(
        ['id' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id()],
        ['name' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->value],
    );

    $idNumber = '63-1234567A63';

    $this->actingAs($user)
        ->withSession([
            'application.track' => ApplicationTrackEnum::Apprentice->value,
            'application.intake_period_id' => $seeded['intakeId'],
            'application.level_id' => $seeded['level']->id,
            'application.department_id' => $seeded['departmentId'],
            'application.department_level_id' => $seeded['departmentLevelId'],
            'application.course_id' => $seeded['courseId'],
            'application.mode_of_study_id' => $seeded['blockReleaseModeId'],
            'registration.id_number' => $idNumber,
            'registration.id_type_id' => $idType->id,
        ])
        ->post(route('portal.store-application'), [
            'first_name' => 'App',
            'last_name' => 'Rentice',
            'gender_id' => $gender->id,
            'marital_status_id' => $maritalStatus->id,
            'title_id' => $title->id,
            'mode_of_study_id' => $seeded['blockReleaseModeId'],
            'id_type_id' => $idType->id,
            'id_number' => $idNumber,
            'date_of_birth' => '2000-01-01',
            'address_1' => 'Address 1',
            'address_2' => 'Address 2',
            'address_3' => 'Address 3',
            'email' => $user->email,
            'phone_number' => '0777000000',
            'next_of_kin_name' => 'Kin Name',
            'next_of_kin_address_1' => 'Kin 1',
            'next_of_kin_address_2' => 'Kin 2',
            'next_of_kin_address_3' => 'Kin 3',
            'relationship_id' => $relationship->id,
            'next_of_kin_phone_number' => '0777111111',
            'department_id' => $seeded['departmentId'],
            'level_id' => $seeded['departmentLevelId'],
            'course_id' => $seeded['courseId'],
            'disability_status' => 'no',
            'employer' => 'ACME Engineering',
            'apprentice_number' => 'APP-2026-001',
        ])
        ->assertRedirect(route('portal.applications'));

    $student = Student::query()->where('user_id', $user->id)->first();
    expect($student)->not->toBeNull()
        ->and($student->id_number)->toBe($idNumber);

    expect($student->contacts()->exists())->toBeTrue()
        ->and($student->nextOfKins()->exists())->toBeTrue();

    $intake = \App\Models\Institution\IntakePeriod::query()->findOrFail($seeded['intakeId']);

    $apprentice = StudentApprentice::query()
        ->where('student_id', $student->id)
        ->where('calendar_year', $intake->calendarYearInteger())
        ->first();

    expect($apprentice)->not->toBeNull()
        ->and($apprentice->employer)->toBe('ACME Engineering')
        ->and($apprentice->apprentice_number)->toBe('APP-2026-001');

    $application = $student->applications()->latest()->first();
    expect($application)->not->toBeNull()
        ->and($application->institution_department_id)->toBe($seeded['departmentId'])
        ->and($application->department_level_id)->toBe($seeded['departmentLevelId'])
        ->and($application->department_course_id)->toBe($seeded['courseId'])
        ->and($application->mode_of_study_id)->toBe($seeded['blockReleaseModeId'])
        ->and($application->intake_period_id)->toBe($seeded['intakeId']);

    expect(ApplicationFee::query()->where('user_id', $user->id)->exists())->toBeFalse();
    expect(session('application.track'))->toBeNull();
});

test('apprentice application requires employer details on store', function () {
    $seeded = seedGuestRegistrationProgramme();
    $user = createTrackApplicant();

    $this->actingAs($user)
        ->withSession([
            'application.track' => ApplicationTrackEnum::Apprentice->value,
            'application.intake_period_id' => $seeded['intakeId'],
            'application.level_id' => $seeded['level']->id,
        ])
        ->from(route('portal.application.apprentice'))
        ->post(route('portal.store-application'), [
            'first_name' => 'App',
            'last_name' => 'Rentice',
            'employer' => '',
            'apprentice_number' => '',
        ])
        ->assertSessionHasErrors(['employer', 'apprentice_number']);
});
test('apprentice reapply from hub does not redirect to fee payment', function () {
    $seeded = seedGuestRegistrationProgramme();
    $seeded['level']->update(['has_application_fee_payment' => true]);
    $user = createTrackApplicant();

    $title = Title::query()->firstOrCreate(['name' => 'Mr Apprentice Hub']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male Apprentice Hub']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single Apprentice Hub']);
    $idType = IdType::query()->firstOrCreate(['name' => 'National ID Apprentice Hub']);

    $student = Student::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'id_type_id' => $idType->id,
        'id_number' => '63-7654321H63',
        'date_of_birth' => '2000-01-01',
        'meta_data' => [
            'returning_student' => [
                'path' => 'reapply',
                'acknowledged_at' => now()->toIso8601String(),
                'intake_period_id' => $seeded['intakeId'],
            ],
        ],
    ]);

    $intake = \App\Models\Institution\IntakePeriod::query()->findOrFail($seeded['intakeId']);
    StudentApprentice::query()->create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'calendar_year' => $intake->calendarYearInteger(),
        'employer' => 'Existing Employer',
        'apprentice_number' => 'APP-EXISTING',
    ]);

    $this->actingAs($user)
        ->post(route('portal.profile.applications.select-level'), [
            'level_id' => $seeded['level']->id,
            'intake_period_id' => $seeded['intakeId'],
        ])
        ->assertRedirect(route('portal.profile.applications'))
        ->assertSessionHasErrors('error');

    expect(ApplicationFee::query()->where('user_id', $user->id)->exists())->toBeFalse();
});

test('apprentice level options stay on level selection for wizard entry', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    $user = createTrackApplicant();

    $this->actingAs($user)
        ->withSession(['application.track' => ApplicationTrackEnum::Apprentice->value])
        ->get(route('portal.application.level-options'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('portal/application/SelectLevelOption'));
});

test('continuous intake is excluded from regular portal intake list', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    ensureContinuousIntakeOpen();

    $open = app(ApplicationFeeService::class)->openIntakePeriodsForPortal();

    expect($open->every(fn ($intake) => ! $intake->is_continuous))->toBeTrue();
});

test('zimbabwean id validation remains required on create application request', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    $user = createTrackApplicant();

    $response = $this->actingAs($user)
        ->withSession([
            'application.track' => ApplicationTrackEnum::Regular->value,
            'registration.id_number' => '63-1234567A63',
        ])
        ->from(route('portal.application.create'))
        ->post(route('portal.store-application'), [
            'first_name' => 'Test',
            'last_name' => 'Student',
            'gender_id' => 1,
            'marital_status_id' => 1,
            'title_id' => 1,
            'mode_of_study_id' => 1,
            'id_type_id' => 1,
            'id_number' => 'invalid-id',
            'address_1' => 'A',
            'address_2' => 'B',
            'address_3' => 'C',
            'email' => $user->email,
            'phone_number' => '0777000000',
            'next_of_kin_name' => 'Kin',
            'next_of_kin_address_1' => 'K1',
            'next_of_kin_address_2' => 'K2',
            'next_of_kin_address_3' => 'K3',
            'relationship_id' => 1,
            'next_of_kin_phone_number' => '0777111111',
            'department_id' => 1,
            'level_id' => 1,
            'course_id' => 1,
            'disability_status' => 'no',
            'date_of_birth' => '2000-01-01',
        ]);

    $response->assertSessionHasErrors('id_number');
});

test('continuous application submit succeeds when regular intake is closed', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Closed->value);
    $continuous = ensureContinuousIntakeOpen();
    $user = createTrackApplicant();
    $tenant = Tenant::query()->firstOrFail();

    $title = Title::query()->firstOrCreate(['name' => 'Mr Continuous Track']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male Continuous Track']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single Continuous Track']);
    $relationship = Relationship::query()->firstOrCreate(['name' => 'Guardian Continuous Track']);
    $mode = ModeOfStudy::query()->firstOrCreate(
        ['name' => ModeOfStudyEnum::FULL_TIME->value],
        ['description' => 'Full Time'],
    );

    if (! IdType::query()->find(IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id())) {
        IdType::query()->insert([
            'id' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id(),
            'name' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $sdp = Level::query()->firstOrCreate(
        ['name' => LevelEnum::SDP->value],
        [
            'description' => 'SDP',
            'position' => 9,
            'show_on_current_application_period' => true,
            'has_application_fee_payment' => false,
        ],
    );
    $sdp->update([
        'show_on_current_application_period' => true,
        'has_application_fee_payment' => false,
    ]);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
    ]);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $sdp->id,
        'show_on_current_application_period' => true,
    ]);
    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
        'show_on_current_application_period' => true,
    ]);

    $idNumber = '63-1234567N63';

    $this->actingAs($user)
        ->withSession([
            'application.track' => ApplicationTrackEnum::Continuous->value,
            'application.intake_period_id' => $continuous->id,
            'registration.id_number' => $idNumber,
            'registration.id_type_id' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id(),
        ])
        ->from(route('portal.application.confirm'))
        ->post(route('portal.store-application'), [
            'first_name' => 'Gorge',
            'last_name' => 'Shines',
            'middle_name' => 'Bana',
            'gender_id' => $gender->id,
            'marital_status_id' => $maritalStatus->id,
            'title_id' => $title->id,
            'mode_of_study_id' => $mode->id,
            'id_type_id' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id(),
            'id_number' => $idNumber,
            'date_of_birth' => '2000-01-01',
            'address_1' => 'Address 1',
            'address_2' => 'Address 2',
            'address_3' => 'Address 3',
            'email' => $user->email,
            'phone_number' => '0777000000',
            'next_of_kin_name' => 'Kin Name',
            'next_of_kin_address_1' => 'Kin 1',
            'next_of_kin_address_2' => 'Kin 2',
            'next_of_kin_address_3' => 'Kin 3',
            'relationship_id' => $relationship->id,
            'next_of_kin_phone_number' => '0777111111',
            'department_id' => $institutionDepartment->id,
            'level_id' => $departmentLevel->id,
            'course_id' => $departmentCourse->id,
            'disability_status' => 'no',
        ])
        ->assertRedirect(route('portal.applications'));

    $student = Student::query()->where('user_id', $user->id)->first();
    expect($student)->not->toBeNull()
        ->and($student->id_number)->toBe($idNumber);

    $application = $student->applications()->latest()->first();
    expect($application)->not->toBeNull()
        ->and($application->intake_period_id)->toBe($continuous->id)
        ->and($application->institution_department_id)->toBe($institutionDepartment->id)
        ->and($application->department_level_id)->toBe($departmentLevel->id);

    expect(session('application.track'))->toBeNull();
});

test('resolveIntakeForApplicationSubmit uses continuous intake when regular is closed', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Closed->value);
    $continuous = ensureContinuousIntakeOpen();
    $user = createTrackApplicant();

    $intake = app(ApplicationFeeService::class)->resolveIntakeForApplicationSubmit(
        $user,
        ApplicationTrackEnum::Continuous,
    );

    expect($intake->id)->toBe($continuous->id)
        ->and($intake->is_continuous)->toBeTrue();
});
