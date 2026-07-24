<?php

use App\Enums\Rbac\RoleEnum;
use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Institution\LevelEnum;
use App\Enums\Students\ApplicationTrackEnum;
use App\Helpers\DropdownHelper;
use App\Models\Rbac\Role;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\Level;
use App\Services\Students\ApplicationEligibilityService;
use App\Services\Students\IntakePeriodOrderingService;
use App\Services\Students\RegistrationIntentSession;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Role::findOrCreate(RoleEnum::STUDENT->value, 'web');
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    Level::factory()->create(['show_on_current_application_period' => true]);
});

test('guest can select track without an account', function () {
    $response = $this->get(route('portal.register.track'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('portal/guest/SelectRegistrationTrack')
        ->has('tracks')
    );
});

test('guest path step labels Direct track and lists only open application period levels', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    ensureContinuousIntakeOpen();

    Level::query()->update(['show_on_current_application_period' => false]);

    $nc = Level::query()->firstOrCreate(
        ['name' => LevelEnum::NC->value],
        ['description' => 'NC', 'position' => 5],
    );
    $hnd = Level::query()->firstOrCreate(
        ['name' => LevelEnum::HND->value],
        ['description' => 'HND', 'position' => 7],
    );
    $sdp = Level::query()->firstOrCreate(
        ['name' => LevelEnum::SDP->value],
        ['description' => 'SDP', 'position' => 9],
    );
    $abma3 = Level::query()->firstOrCreate(
        ['name' => LevelEnum::ABMA_LEVEL_3->value],
        ['description' => 'ABMA 3', 'position' => 1],
    );
    $abma4 = Level::query()->firstOrCreate(
        ['name' => LevelEnum::ABMA_LEVEL_4->value],
        ['description' => 'ABMA 4', 'position' => 2],
    );

    $nc->update(['show_on_current_application_period' => true]);
    $hnd->update(['show_on_current_application_period' => true]);
    $sdp->update(['show_on_current_application_period' => false]);
    $abma3->update(['show_on_current_application_period' => true]);
    $abma4->update(['show_on_current_application_period' => true]);

    expect(__('trans.application_track_regular'))->toBe('Direct')
        ->and(__('trans.application_track_apprentice_description'))
        ->not->toContain('application fee')
        ->not->toContain('Application Fee')
        ->and(__('trans.registration_express_apprentice_hint'))
        ->not->toContain('application fee')
        ->not->toContain('Application Fee');

    $this->get(route('portal.register.track'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/guest/SelectRegistrationTrack')
            ->where('tracks', function ($tracks) {
                $regular = collect($tracks)->firstWhere('value', ApplicationTrackEnum::Regular->value);
                $apprentice = collect($tracks)->firstWhere('value', ApplicationTrackEnum::Apprentice->value);

                if ($regular === null || $apprentice === null) {
                    return false;
                }

                $description = (string) ($regular['description'] ?? '');
                $apprenticeDescription = (string) ($apprentice['description'] ?? '');

                return ($regular['label'] ?? null) === 'Direct'
                    && str_contains($description, LevelEnum::NC->value)
                    && str_contains($description, LevelEnum::HND->value)
                    && str_contains($description, 'ABMA')
                    && ! str_contains($description, 'ABMA Level')
                    && ! str_contains($description, LevelEnum::SDP->value)
                    && ! str_contains(strtolower($apprenticeDescription), 'application fee');
            })
        );
});

test('guest track selection redirects to level for regular track', function () {
    $regular = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    ensureContinuousIntakeOpen();

    $this->post(route('portal.register.select-track'), [
        'track' => ApplicationTrackEnum::Regular->value,
    ])->assertRedirect(route('portal.register.level'));

    expect(session(RegistrationIntentSession::TRACK_KEY))->toBe(ApplicationTrackEnum::Regular->value)
        ->and(session(RegistrationIntentSession::INTAKE_KEY))->toBe($regular->id);
});

test('guest apprentice track redirects to level and binds regular open intake', function () {
    $regular = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    ensureContinuousIntakeOpen();

    $this->post(route('portal.register.select-track'), [
        'track' => ApplicationTrackEnum::Apprentice->value,
    ])->assertRedirect(route('portal.register.level'));

    expect(session(RegistrationIntentSession::TRACK_KEY))->toBe(ApplicationTrackEnum::Apprentice->value)
        ->and(session(RegistrationIntentSession::INTAKE_KEY))->toBe($regular->id)
        ->and(session(RegistrationIntentSession::REQUIRES_FEE_KEY))->toBeNull();
});

test('guest regular and apprentice tracks are unavailable when only continuous intake is open', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Closed->value);
    ensureContinuousIntakeOpen();

    $tracks = collect(app(ApplicationEligibilityService::class)->availableTracks())
        ->map->value
        ->all();

    expect($tracks)->toBe([ApplicationTrackEnum::Continuous->value]);

    // Continuous alone still shows the picker (SDP / OJET focus required).
    $this->get(route('portal.register.track'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/guest/SelectRegistrationTrack')
            ->where('tracks', fn ($pageTracks) => collect($pageTracks)->pluck('value')->all() === [ApplicationTrackEnum::Continuous->value])
        );

    $this->from(route('portal.register.track'))
        ->post(route('portal.register.select-track'), [
            'track' => ApplicationTrackEnum::Regular->value,
        ])
        ->assertSessionHasErrors('track');

    $this->from(route('portal.register.track'))
        ->post(route('portal.register.select-track'), [
            'track' => ApplicationTrackEnum::Apprentice->value,
        ])
        ->assertSessionHasErrors('track');
});

test('guest sdp focus skips level and binds continuous intake', function () {
    $continuous = ensureContinuousIntakeOpen();
    $sdp = Level::query()->firstOrCreate(
        ['name' => LevelEnum::SDP->value],
        ['description' => 'SDP', 'position' => 1, 'show_on_current_application_period' => true],
    );
    $sdp->update(['show_on_current_application_period' => true]);

    $this->post(route('portal.register.select-track'), [
        'track' => ApplicationTrackEnum::Continuous->value,
        'continuous_focus' => 'sdp',
    ])->assertRedirect(route('portal.register.programme'));

    expect(session(RegistrationIntentSession::LEVEL_KEY))->toBe($sdp->id)
        ->and(session(RegistrationIntentSession::INTAKE_KEY))->toBe($continuous->id)
        ->and(session(RegistrationIntentSession::CONTINUOUS_FOCUS_KEY))->toBe('sdp');
});

test('guest ojet focus redirects to level with continuous intake', function () {
    $continuous = ensureContinuousIntakeOpen();

    $this->post(route('portal.register.select-track'), [
        'track' => ApplicationTrackEnum::Continuous->value,
        'continuous_focus' => 'ojet',
    ])->assertRedirect(route('portal.register.level'));

    expect(session(RegistrationIntentSession::INTAKE_KEY))->toBe($continuous->id)
        ->and(session(RegistrationIntentSession::CONTINUOUS_FOCUS_KEY))->toBe('ojet')
        ->and(session(RegistrationIntentSession::LEVEL_KEY))->toBeNull();
});

test('guest level without application fee omits fee from stepper props', function () {
    $seeded = seedGuestRegistrationProgramme();
    $seeded['level']->update(['has_application_fee_payment' => false]);

    $this->withSession([
        RegistrationIntentSession::TRACK_KEY => ApplicationTrackEnum::Regular->value,
        RegistrationIntentSession::LEVEL_KEY => $seeded['level']->id,
        RegistrationIntentSession::INTAKE_KEY => $seeded['intakeId'],
        RegistrationIntentSession::REQUIRES_FEE_KEY => false,
    ])->get(route('portal.register.programme'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/guest/RegistrationProgrammeFinder')
            ->where('requiresFee', false)
            ->where('stepperVariant', 'regular')
        );
});

test('guest apprentice programme page uses apprentice stepper without fee', function () {
    $seeded = seedGuestRegistrationProgramme();

    $this->withSession([
        RegistrationIntentSession::TRACK_KEY => ApplicationTrackEnum::Apprentice->value,
        RegistrationIntentSession::LEVEL_KEY => $seeded['level']->id,
        RegistrationIntentSession::INTAKE_KEY => $seeded['intakeId'],
        RegistrationIntentSession::REQUIRES_FEE_KEY => false,
    ])->get(route('portal.register.programme'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/guest/RegistrationProgrammeFinder')
            ->where('requiresFee', false)
            ->where('stepperVariant', 'apprentice')
        );
});

test('guest level selection stores requires_fee from level setting', function () {
    $seeded = seedGuestRegistrationProgramme();
    $seeded['level']->update(['has_application_fee_payment' => false]);

    $this->withSession([
        RegistrationIntentSession::TRACK_KEY => ApplicationTrackEnum::Regular->value,
    ])->post(route('portal.register.select-level'), [
        'level_id' => $seeded['level']->id,
        'intake_period_id' => $seeded['intakeId'],
    ])->assertRedirect(route('portal.register.programme'));

    expect(session(RegistrationIntentSession::REQUIRES_FEE_KEY))->toBeFalse();
});

test('guest level selection redirects to programme finder', function () {
    $seeded = seedGuestRegistrationProgramme();

    $this->withSession([
        RegistrationIntentSession::TRACK_KEY => ApplicationTrackEnum::Regular->value,
    ])->post(route('portal.register.select-level'), [
        'level_id' => $seeded['level']->id,
        'intake_period_id' => $seeded['intakeId'],
    ])->assertRedirect(route('portal.register.programme'));

    expect(session(RegistrationIntentSession::LEVEL_KEY))->toBe($seeded['level']->id);
});

test('guest programme selection marks account ready', function () {
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
        ->and(session(RegistrationIntentSession::DEPARTMENT_KEY))->toBe($seeded['departmentId']);
});

test('guest programmes api returns tree for level', function () {
    $seeded = seedGuestRegistrationProgramme();

    $response = $this->getJson(route('v1.guest.enrollment.programmes', [
        'track' => ApplicationTrackEnum::Regular->value,
        'level_id' => $seeded['level']->id,
    ]));

    $response->assertOk()
        ->assertJsonPath('available', true)
        ->assertJsonStructure(['available', 'departments', 'unavailableReason']);
});

test('guest programmes api returns empty when no programmes shown', function () {
    $seeded = seedGuestRegistrationProgramme();

    DepartmentLevel::query()
        ->whereKey($seeded['departmentLevelId'])
        ->update(['show_on_current_application_period' => false]);

    $response = $this->getJson(route('v1.guest.enrollment.programmes', [
        'track' => ApplicationTrackEnum::Regular->value,
        'level_id' => $seeded['level']->id,
    ]));

    $response->assertOk()
        ->assertJsonPath('available', false);
});

test('apprentice programmes api only returns block release modes', function () {
    $seeded = seedGuestRegistrationProgramme();

    $response = $this->getJson(route('v1.guest.enrollment.programmes', [
        'track' => ApplicationTrackEnum::Apprentice->value,
        'level_id' => $seeded['level']->id,
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

    expect($modeIds)->toBe([$seeded['blockReleaseModeId']])
        ->and($modeIds)->not->toContain($seeded['modeId']);
});

test('apprentice guest store redirects to create application wizard', function () {
    $seeded = seedGuestRegistrationProgramme();
    $email = 'apprentice.guest.'.uniqid().'@example.com';

    $response = $this->withSession(guestRegistrationIntentSession(ApplicationTrackEnum::Apprentice, $seeded))
        ->post(route('portal.store'), [
            'registration_path' => 'zimbabwean',
            'first_name' => 'App',
            'last_name' => 'Rentice',
            'email' => $email,
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'id_number' => '44-0666777A44',
            'acknowledged_advert' => true,
        ]);

    $response->assertRedirect(route('portal.application.create'));
    $this->assertAuthenticated();
    expect(session('application.track'))->toBe(ApplicationTrackEnum::Apprentice->value);
});

test('apprentice express I1 guest flow binds regular intake through programme to account without fee', function () {
    $seeded = seedGuestRegistrationProgramme();

    $this->post(route('portal.register.select-track'), [
        'track' => ApplicationTrackEnum::Apprentice->value,
    ])->assertRedirect(route('portal.register.level'));

    expect(session(RegistrationIntentSession::INTAKE_KEY))->toBe($seeded['intakeId']);

    $this->post(route('portal.register.select-level'), [
        'level_id' => $seeded['level']->id,
        'intake_period_id' => $seeded['intakeId'],
    ])->assertRedirect(route('portal.register.programme'));

    expect(session(RegistrationIntentSession::REQUIRES_FEE_KEY))->toBeFalse();

    $this->post(route('portal.register.select-programme'), [
        'department_id' => $seeded['departmentId'],
        'department_level_id' => $seeded['departmentLevelId'],
        'course_id' => $seeded['courseId'],
        'mode_of_study_id' => $seeded['blockReleaseModeId'],
    ])->assertRedirect(route('portal.register.account'));

    $this->get(route('portal.register.account'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/guest/RegistrationUserForm')
            ->where('stepperVariant', 'apprentice')
            ->where('requiresFee', false)
        );
});

test('apprentice store without programme creates no user', function () {
    $seeded = seedGuestRegistrationProgramme();
    $email = 'orphan.apprentice.'.uniqid().'@example.com';

    $this->withSession([
        RegistrationIntentSession::TRACK_KEY => ApplicationTrackEnum::Apprentice->value,
        RegistrationIntentSession::LEVEL_KEY => $seeded['level']->id,
        RegistrationIntentSession::INTAKE_KEY => $seeded['intakeId'],
        RegistrationIntentSession::INSTRUCTIONS_KEY => true,
    ])->post(route('portal.store'), [
        'registration_path' => 'zimbabwean',
        'first_name' => 'Orphan',
        'last_name' => 'Apprentice',
        'email' => $email,
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
        'id_number' => '44-0777888B44',
        'acknowledged_advert' => true,
    ])->assertRedirect(route('portal.register.track'));

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => $email]);
});

test('admin intake ordering places continuous second to most recent regular', function () {
    Cache::forget('all_intake_periods');

    $regularOlder = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Closed->value);
    $regularOlder->update([
        'end_date' => now()->subYear()->toDateString(),
        'name' => 'Older Regular',
    ]);

    $regularRecent = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Closed->value);
    $regularRecent->update([
        'end_date' => now()->subMonth()->toDateString(),
        'name' => 'Recent Regular',
    ]);

    $continuous = ensureContinuousIntakeOpen();
    $continuous->update([
        'end_date' => now()->addYears(10)->toDateString(),
        'name' => 'Continuous Far Future',
    ]);

    $ordering = app(IntakePeriodOrderingService::class);
    $ordered = $ordering->orderedForAdminDropdown(true);

    expect($ordered->count())->toBeGreaterThanOrEqual(2)
        ->and((int) $ordered->first()->id)->toBe($regularRecent->id)
        ->and((bool) $ordered->get(1)?->is_continuous)->toBeTrue()
        ->and((int) $ordered->get(1)->id)->toBe($continuous->id);

    $default = $ordering->defaultAdminIntakePeriod();
    expect($default)->not->toBeNull()
        ->and($default->is_continuous)->toBeFalse()
        ->and((int) $default->id)->toBe($regularRecent->id);

    Cache::forget('all_intake_periods');
    $dropdown = DropdownHelper::getIntakePeriods()->values();
    expect((bool) ($dropdown->get(1)->is_continuous ?? false))->toBeTrue();
});
