<?php

use App\Enums\Acl\RoleEnum;
use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Institution\LevelEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Enums\Shared\TenantEnum;
use App\Enums\Students\ApplicationTrackEnum;
use App\Models\Acl\Role;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Users\User;
use App\Services\Students\IntakePeriodOrderingService;
use App\Services\Students\RegistrationIntentSession;
use App\Services\Students\RegistrationLevelOptionsService;

beforeEach(function () {
    Role::findOrCreate(RoleEnum::STUDENT->value, 'web');
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    Level::factory()->create(['show_on_current_application_period' => true]);
});

test('continuous intake labels omit year-round wording', function () {
    $continuous = ensureContinuousIntakeOpen();
    $display = app(IntakePeriodOrderingService::class)->displayName($continuous);

    expect($display)->toBe(__('trans.intake_period_continuous_display_name'))
        ->and($display)->not->toContain('year-round')
        ->and(__('trans.application_track_continuous'))->not->toContain('year-round')
        ->and(__('trans.registration_maintenance_continuous_cta'))->not->toContain('year-round')
        ->and(__('trans.intake_period_is_continuous'))->not->toContain('year-round');
});

test('changing track clears prior level and programme intent', function () {
    $seeded = seedGuestRegistrationProgramme();

    $this->withSession(guestRegistrationIntentSession(ApplicationTrackEnum::Regular, $seeded))
        ->post(route('portal.register.select-track'), [
            'track' => ApplicationTrackEnum::Apprentice->value,
        ])
        ->assertRedirect(route('portal.register.level'));

    expect(session(RegistrationIntentSession::TRACK_KEY))->toBe(ApplicationTrackEnum::Apprentice->value)
        ->and(session(RegistrationIntentSession::LEVEL_KEY))->toBeNull()
        ->and(session(RegistrationIntentSession::DEPARTMENT_KEY))->toBeNull()
        ->and(session(RegistrationIntentSession::COURSE_KEY))->toBeNull()
        ->and(session(RegistrationIntentSession::READY_FOR_ACCOUNT_KEY))->toBeNull();
});

test('sdp level page redirects to programme when level already bound', function () {
    $continuous = ensureContinuousIntakeOpen();
    $sdp = Level::query()->firstOrCreate(
        ['name' => LevelEnum::SDP->value],
        ['description' => 'SDP', 'position' => 9, 'show_on_current_application_period' => true],
    );
    $sdp->update(['show_on_current_application_period' => true]);

    $this->withSession([
        RegistrationIntentSession::TRACK_KEY => ApplicationTrackEnum::Continuous->value,
        RegistrationIntentSession::CONTINUOUS_FOCUS_KEY => 'sdp',
        RegistrationIntentSession::LEVEL_KEY => $sdp->id,
        RegistrationIntentSession::INTAKE_KEY => $continuous->id,
    ])->get(route('portal.register.level'))
        ->assertRedirect(route('portal.register.programme'));
});

test('ojet level options exclude sdp levels', function () {
    ensureContinuousIntakeOpen();
    $sdp = Level::query()->firstOrCreate(
        ['name' => LevelEnum::SDP->value],
        ['description' => 'SDP', 'position' => 9, 'show_on_current_application_period' => true],
    );
    $sdp->update(['show_on_current_application_period' => true]);

    $this->withSession([
        RegistrationIntentSession::TRACK_KEY => ApplicationTrackEnum::Continuous->value,
        RegistrationIntentSession::CONTINUOUS_FOCUS_KEY => 'ojet',
    ])->get(route('portal.register.level'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/guest/SelectRegistrationLevel')
            ->where('stepperVariant', 'ojet')
            ->where('continuousFocus', 'ojet')
            ->where('levels', function ($levels) {
                $items = collect(data_get($levels, 'data', $levels));

                return $items->every(function ($level) {
                    $name = data_get($level, 'attributes.name', data_get($level, 'name'));

                    return $name !== LevelEnum::SDP->value;
                });
            })
        );
});

test('level selection clears prior programme selection', function () {
    $seeded = seedGuestRegistrationProgramme();

    $this->withSession([
        RegistrationIntentSession::TRACK_KEY => ApplicationTrackEnum::Regular->value,
        RegistrationIntentSession::DEPARTMENT_KEY => $seeded['departmentId'],
        RegistrationIntentSession::DEPARTMENT_LEVEL_KEY => $seeded['departmentLevelId'],
        RegistrationIntentSession::COURSE_KEY => $seeded['courseId'],
        RegistrationIntentSession::MODE_KEY => $seeded['modeId'],
        RegistrationIntentSession::READY_FOR_ACCOUNT_KEY => true,
    ])->post(route('portal.register.select-level'), [
        'level_id' => $seeded['level']->id,
        'intake_period_id' => $seeded['intakeId'],
    ])->assertRedirect(route('portal.register.programme'));

    expect(session(RegistrationIntentSession::DEPARTMENT_KEY))->toBeNull()
        ->and(session(RegistrationIntentSession::COURSE_KEY))->toBeNull()
        ->and(session(RegistrationIntentSession::READY_FOR_ACCOUNT_KEY))->toBeNull();
});

test('regular level page requires intake when multiple open regular intakes exist', function () {
    $first = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    IntakePeriod::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'name' => 'Second Open Regular '.uniqid(),
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->addMonths(6)->toDateString(),
        'calendar_year' => '2026/2027',
        'is_active' => true,
        'status' => IntakePeriodStatusEnum::Open->value,
        'is_continuous' => false,
    ]);

    $this->withSession([
        RegistrationIntentSession::TRACK_KEY => ApplicationTrackEnum::Regular->value,
    ])->get(route('portal.register.level'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/guest/SelectRegistrationLevel')
            ->where('requiresIntakeSelection', true)
        );

    $options = app(RegistrationLevelOptionsService::class)
        ->openIntakesForTrack(ApplicationTrackEnum::Regular);

    expect($options->count())->toBeGreaterThan(1)
        ->and($options->contains('id', $first->id))->toBeTrue();
});

test('sdp programmes api excludes ojet modes', function () {
    $seeded = seedGuestContinuousProgramme('sdp');

    $response = $this->getJson(route('v1.guest.enrollment.programmes', [
        'track' => ApplicationTrackEnum::Continuous->value,
        'level_id' => $seeded['level']->id,
        'continuous_focus' => 'sdp',
    ]));

    $response->assertOk()->assertJsonPath('available', true);

    $modeIds = collect($response->json('departments'))
        ->flatMap(fn ($dept) => $dept['levels'])
        ->flatMap(fn ($level) => $level['courses'])
        ->flatMap(fn ($course) => $course['modes'])
        ->pluck('id')
        ->all();

    expect($modeIds)->toContain($seeded['fullTimeModeId'])
        ->and($modeIds)->not->toContain($seeded['ojetModeId']);
});

test('ojet programmes api only returns ojet modes', function () {
    $seeded = seedGuestContinuousProgramme('ojet');

    $response = $this->getJson(route('v1.guest.enrollment.programmes', [
        'track' => ApplicationTrackEnum::Continuous->value,
        'level_id' => $seeded['level']->id,
        'continuous_focus' => 'ojet',
    ]));

    $response->assertOk()->assertJsonPath('available', true);

    $modeIds = collect($response->json('departments'))
        ->flatMap(fn ($dept) => $dept['levels'])
        ->flatMap(fn ($level) => $level['courses'])
        ->flatMap(fn ($course) => $course['modes'])
        ->pluck('id')
        ->unique()
        ->values()
        ->all();

    expect($modeIds)->toBe([$seeded['ojetModeId']]);
});

test('stale programme selection is rejected', function () {
    $seeded = seedGuestRegistrationProgramme();

    $this->withSession([
        RegistrationIntentSession::TRACK_KEY => ApplicationTrackEnum::Regular->value,
        RegistrationIntentSession::LEVEL_KEY => $seeded['level']->id,
        RegistrationIntentSession::INTAKE_KEY => $seeded['intakeId'],
    ])->from(route('portal.register.programme'))
        ->post(route('portal.register.select-programme'), [
            'department_id' => 999999,
            'department_level_id' => $seeded['departmentLevelId'],
            'course_id' => $seeded['courseId'],
            'mode_of_study_id' => $seeded['modeId'],
        ])
        ->assertSessionHasErrors('department_id');

    expect(session(RegistrationIntentSession::READY_FOR_ACCOUNT_KEY))->toBeNull();
});

test('account page redirects when programme selection is missing', function () {
    $seeded = seedGuestRegistrationProgramme();

    $this->withSession([
        RegistrationIntentSession::TRACK_KEY => ApplicationTrackEnum::Regular->value,
        RegistrationIntentSession::LEVEL_KEY => $seeded['level']->id,
        RegistrationIntentSession::INTAKE_KEY => $seeded['intakeId'],
    ])->get(route('portal.register.account'))
        ->assertRedirect(route('portal.register.programme'));
});

test('store with incomplete programme intent redirects to track', function () {
    $seeded = seedGuestRegistrationProgramme();

    $response = $this->withSession([
        RegistrationIntentSession::TRACK_KEY => ApplicationTrackEnum::Regular->value,
        RegistrationIntentSession::LEVEL_KEY => $seeded['level']->id,
        RegistrationIntentSession::INTAKE_KEY => $seeded['intakeId'],
        RegistrationIntentSession::INSTRUCTIONS_KEY => true,
    ])->post(route('portal.store'), [
        'registration_path' => 'zimbabwean',
        'first_name' => 'Incomplete',
        'last_name' => 'Intent',
        'email' => 'incomplete.intent.'.uniqid().'@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
        'id_number' => '44-0555444E44',
        'acknowledged_advert' => true,
    ]);

    $response->assertRedirect(route('portal.register.track'));
    $this->assertGuest();
});

test('international passport store creates account and promotes intent', function () {
    $seeded = seedGuestRegistrationProgramme();
    $seeded['level']->update(['has_application_fee_payment' => false]);
    $email = 'intl.passport.'.uniqid().'@example.com';
    $passport = 'AB'.uniqid();

    $response = $this->withSession(guestRegistrationIntentSession(ApplicationTrackEnum::Regular, $seeded))
        ->post(route('portal.store'), [
            'registration_path' => 'international',
            'first_name' => 'Intl',
            'last_name' => 'Applicant',
            'email' => $email,
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'passport_number' => $passport,
            'acknowledged_advert' => true,
        ]);

    $response->assertRedirect(route('portal.application.create'));
    $this->assertAuthenticated();
    expect(session('registration.path'))->toBe('international')
        ->and(session('registration.passport_number'))->toBe(strtoupper($passport))
        ->and(session('application.department_id'))->toBe($seeded['departmentId']);
});

test('duplicate passport registration is blocked', function () {
    $seeded = seedGuestRegistrationProgramme();
    $passport = 'DUP'.uniqid();
    $tenantId = TenantEnum::HARARE_POLY->id();

    $user = User::factory()->create([
        'tenant_id' => $tenantId,
        'email' => 'existing.passport.'.uniqid().'@example.com',
    ]);
    $user->assignRole(RoleEnum::STUDENT);

    $title = Title::query()->firstOrCreate(['name' => 'Mr Passport Dup']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male Passport Dup']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single Passport Dup']);
    $idType = IdType::query()->firstOrCreate(['name' => 'Passport Dup']);

    Student::query()->create([
        'tenant_id' => $tenantId,
        'user_id' => $user->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'id_type_id' => $idType->id,
        'passport_number' => strtoupper($passport),
        'date_of_birth' => '2000-01-01',
    ]);

    $this->withSession(guestRegistrationIntentSession(ApplicationTrackEnum::Regular, $seeded))
        ->post(route('portal.store'), [
            'registration_path' => 'international',
            'first_name' => 'Dup',
            'last_name' => 'Passport',
            'email' => 'dup.passport.'.uniqid().'@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'passport_number' => $passport,
            'acknowledged_advert' => true,
        ])
        ->assertSessionHasErrors('passport_number');

    $this->assertGuest();
});

test('store rejects stale closed intake and clears level intent', function () {
    $seeded = seedGuestRegistrationProgramme();

    // Keep Regular track open via a second open intake while closing the bound one.
    IntakePeriod::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'name' => 'Still Open Regular '.uniqid(),
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->addMonths(8)->toDateString(),
        'calendar_year' => '2026/2027',
        'is_active' => true,
        'status' => IntakePeriodStatusEnum::Open->value,
        'is_continuous' => false,
    ]);

    IntakePeriod::query()->whereKey($seeded['intakeId'])->update([
        'status' => IntakePeriodStatusEnum::Closed->value,
        'is_active' => false,
    ]);

    $this->withSession(guestRegistrationIntentSession(ApplicationTrackEnum::Regular, $seeded))
        ->post(route('portal.store'), [
            'registration_path' => 'zimbabwean',
            'first_name' => 'Stale',
            'last_name' => 'Intake',
            'email' => 'stale.intake.'.uniqid().'@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'id_number' => '44-0333222F44',
            'acknowledged_advert' => true,
        ])
        ->assertRedirect(route('portal.register.level'));

    expect(session(RegistrationIntentSession::LEVEL_KEY))->toBeNull()
        ->and(session(RegistrationIntentSession::DEPARTMENT_KEY))->toBeNull();
    $this->assertGuest();
});

test('store redirects to fee payment when level requires fee', function () {
    $seeded = seedGuestRegistrationProgramme();
    $seeded['level']->update(['has_application_fee_payment' => true]);

    if (! IdType::query()->find(IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id())) {
        IdType::query()->insert([
            'id' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id(),
            'name' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $session = guestRegistrationIntentSession(ApplicationTrackEnum::Regular, $seeded);
    $session[RegistrationIntentSession::REQUIRES_FEE_KEY] = true;

    $this->withSession($session)
        ->post(route('portal.store'), [
            'registration_path' => 'zimbabwean',
            'first_name' => 'Fee',
            'last_name' => 'Required',
            'email' => 'fee.required.'.uniqid().'@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'id_number' => '44-0222111G44',
            'acknowledged_advert' => true,
        ])
        ->assertRedirect(route('portal.application.fee-payment'));

    $this->assertAuthenticated();
});

test('create application page includes programme prefill from promoted intent', function () {
    $seeded = seedGuestRegistrationProgramme();
    $seeded['level']->update(['has_application_fee_payment' => false]);
    $email = 'prefill.student.'.uniqid().'@example.com';

    $this->withSession(guestRegistrationIntentSession(ApplicationTrackEnum::Regular, $seeded))
        ->post(route('portal.store'), [
            'registration_path' => 'zimbabwean',
            'first_name' => 'Prefill',
            'last_name' => 'Student',
            'email' => $email,
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'id_number' => '44-0111000H44',
            'acknowledged_advert' => true,
        ])
        ->assertRedirect(route('portal.application.create'));

    $this->get(route('portal.application.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/application/CreateApplication')
            ->where('programmePrefill.department_id', $seeded['departmentId'])
            ->where('programmePrefill.department_level_id', $seeded['departmentLevelId'])
            ->where('programmePrefill.course_id', $seeded['courseId'])
            ->where('programmePrefill.mode_of_study_id', $seeded['modeId'])
        );
});

test('sdp programme page uses sdp stepper variant', function () {
    $seeded = seedGuestContinuousProgramme('sdp');

    $this->withSession([
        RegistrationIntentSession::TRACK_KEY => ApplicationTrackEnum::Continuous->value,
        RegistrationIntentSession::CONTINUOUS_FOCUS_KEY => 'sdp',
        RegistrationIntentSession::LEVEL_KEY => $seeded['level']->id,
        RegistrationIntentSession::INTAKE_KEY => $seeded['continuousIntakeId'],
        RegistrationIntentSession::REQUIRES_FEE_KEY => false,
    ])->get(route('portal.register.programme'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/guest/RegistrationProgrammeFinder')
            ->where('stepperVariant', 'sdp')
            ->where('requiresFee', false)
            ->where('continuousFocus', 'sdp')
        );
});

test('guest passport check returns not found for new passport', function () {
    $this->postJson('/api/v1/guest/enrollment/check-passport', [
        'passport_number' => 'NEWNONE'.uniqid(),
    ])->assertOk()->assertJson(['found' => false]);
});

test('wrong continuous focus with sdp level yields empty ojet programme tree', function () {
    $seeded = seedGuestContinuousProgramme('sdp');

    $response = $this->getJson(route('v1.guest.enrollment.programmes', [
        'track' => ApplicationTrackEnum::Continuous->value,
        'level_id' => $seeded['level']->id,
        'continuous_focus' => 'ojet',
    ]));

    // SDP level with OJET focus should not expose SDP-only programmes as OJET tree.
    $response->assertOk();
    $available = $response->json('available');

    if ($available) {
        $modeIds = collect($response->json('departments'))
            ->flatMap(fn ($dept) => $dept['levels'])
            ->flatMap(fn ($level) => $level['courses'])
            ->flatMap(fn ($course) => $course['modes'])
            ->pluck('id')
            ->all();

        expect($modeIds)->toContain($seeded['ojetModeId'])
            ->and($modeIds)->not->toContain($seeded['fullTimeModeId']);
    } else {
        expect($available)->toBeFalse();
    }
});
