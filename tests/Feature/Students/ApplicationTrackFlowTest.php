<?php

use App\Enums\Acl\RoleEnum;
use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Institution\LevelEnum;
use App\Enums\Institution\ModeOfStudyEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Enums\Students\ApplicationTrackEnum;
use App\Models\Acl\Role;
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
        ->assertRedirect(route('portal.application.apprentice'));

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
    $intake = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    $user = createTrackApplicant();

    $title = Title::query()->firstOrCreate(['name' => 'Mr Apprentice Track']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male Apprentice Track']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single Apprentice Track']);
    $idType = IdType::query()->firstOrCreate(['name' => 'National ID Apprentice Track']);

    $this->actingAs($user)
        ->withSession([
            'application.track' => ApplicationTrackEnum::Apprentice->value,
            'application.intake_period_id' => $intake->id,
            'registration.id_number' => '63-1234567A63',
            'registration.id_type_id' => $idType->id,
        ])
        ->post(route('portal.application.apprentice.store'), [
            'employer' => 'ACME Engineering',
            'apprentice_number' => 'APP-2026-001',
        ])
        ->assertRedirect(route('portal.applications'));

    $student = Student::query()->where('user_id', $user->id)->first();
    expect($student)->not->toBeNull()
        ->and($student->id_number)->toBe('63-1234567A63');

    $apprentice = StudentApprentice::query()
        ->where('student_id', $student->id)
        ->where('calendar_year', $intake->calendarYearInteger())
        ->first();

    expect($apprentice)->not->toBeNull()
        ->and($apprentice->employer)->toBe('ACME Engineering')
        ->and($apprentice->apprentice_number)->toBe('APP-2026-001');

    expect(ApplicationFee::query()->where('user_id', $user->id)->exists())->toBeFalse();
    expect(session('application.track'))->toBeNull();
});

test('apprentice express route redirects level options to express form', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    $user = createTrackApplicant();

    $this->actingAs($user)
        ->withSession(['application.track' => ApplicationTrackEnum::Apprentice->value])
        ->get(route('portal.application.level-options'))
        ->assertRedirect(route('portal.application.apprentice'));
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
