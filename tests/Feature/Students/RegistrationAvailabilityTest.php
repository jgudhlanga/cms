<?php

use App\Enums\Acl\RoleEnum;
use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Shared\TenantEnum;
use App\Enums\Students\ApplicationFeeStatusEnum;
use App\Models\Acl\Permission;
use App\Models\Acl\Role;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Students\ApplicationFee;
use App\Models\Students\Student;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Role::findOrCreate(RoleEnum::STUDENT->name(), 'web');
});

function createStudentWithoutProfile(): User
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

test('guest registration page is available when current intake is open', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);

    $this->get(route('portal.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('portal/guest/RegistrationUserForm'));
});

test('guest registration redirects to maintenance when current intake is suspended', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Suspended->value);

    $this->get(route('portal.create'))
        ->assertRedirect(route('portal.registration.maintenance'));
});

test('guest registration redirects to maintenance when current intake is closed', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Closed->value);

    $this->get(route('portal.create'))
        ->assertRedirect(route('portal.registration.maintenance'));
});

test('registration is blocked when latest intake is suspended even if older intake is open', function () {
    $tenantId = TenantEnum::HARARE_POLY->id();

    IntakePeriod::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Older Open Intake',
        'start_date' => now()->subYears(2)->startOfMonth()->toDateString(),
        'end_date' => now()->subYear()->toDateString(),
        'is_active' => true,
        'status' => IntakePeriodStatusEnum::Open->value,
    ]);

    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Suspended->value);

    $this->get(route('portal.create'))
        ->assertRedirect(route('portal.registration.maintenance'));
});

test('portal store is blocked when registration is closed', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Closed->value);

    $this->post(route('portal.store'), [
        'registration_path' => 'zimbabwean',
        'first_name' => 'Blocked',
        'last_name' => 'Student',
        'email' => 'blocked.'.uniqid().'@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
        'id_number' => '44-0999888F99',
        'acknowledged_advert' => true,
    ])->assertRedirect(route('portal.registration.maintenance'));
});

test('student mid registration is redirected to maintenance when intake is suspended', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);

    Level::factory()->create([
        'show_on_current_application_period' => true,
    ]);

    $user = createStudentWithoutProfile();

    $this->actingAs($user)
        ->withSession(['application.track' => 'regular'])
        ->get(route('portal.application.level-options'))
        ->assertOk();

    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Suspended->value);

    $this->actingAs($user)
        ->withSession(['application.track' => 'regular'])
        ->get(route('portal.application.level-options'))
        ->assertRedirect(route('portal.registration.maintenance'));
});

test('guest registration remains available when regular is closed but continuous is open', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Closed->value);
    ensureContinuousIntakeOpen();

    $this->get(route('portal.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('portal/guest/RegistrationUserForm'));
});

test('maintenance page exposes continuous apply when continuous intake is open', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Closed->value);
    ensureContinuousIntakeOpen();

    $this->get(route('portal.registration.maintenance'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/registration/RegistrationMaintenance')
            ->where('continuousOpen', true)
            ->where('continuousApplyUrl', route('portal.register.track')));
});

test('submitted applications page remains accessible when registration is closed', function () {
    $intake = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Closed->value);
    $user = createStudentWithoutProfile();
    $level = Level::factory()->create();

    ApplicationFee::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'intake_period_id' => $intake->id,
        'level_id' => $level->id,
        'status' => ApplicationFeeStatusEnum::SUBMITTED,
    ]);

    $this->actingAs($user)
        ->get(route('portal.applications'))
        ->assertRedirect(route('portal.profile.applications'));
});

test('enrolled student dashboard remains accessible when registration is closed', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Closed->value);

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->assignRole(RoleEnum::STUDENT->name());
    $user->givePermissionTo('viewOwnDashboard:students');

    Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'title_id' => DB::table('titles')->insertGetId([
            'name' => 'Mr Enrolled',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'gender_id' => DB::table('genders')->insertGetId([
            'title' => 'Male Enrolled',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'marital_status_id' => DB::table('marital_statuses')->insertGetId([
            'title' => 'Single Enrolled',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'id_type_id' => DB::table('id_types')->insertGetId([
            'name' => 'National ID Enrolled',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'date_of_birth' => '2000-01-01',
        'student_number' => 'ENROLLED-001',
    ]);

    $this->actingAs($user)
        ->get(route('portal.dashboard'))
        ->assertOk();
});

test('maintenance page redirects to login when registration is open', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);

    $this->get(route('portal.registration.maintenance'))
        ->assertRedirect(route('login'));
});

test('maintenance page renders with suspended status message', function () {
    $intake = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Suspended->value);

    $this->get(route('portal.registration.maintenance'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/registration/RegistrationMaintenance')
            ->where('status', IntakePeriodStatusEnum::Suspended->value)
            ->where('intakeName', $intake->name)
            ->where('message', fn ($message) => str_contains($message, $intake->name)
                && str_contains($message, 'scheduled system maintenance'))
        );
});

test('maintenance page renders distinct closed status message', function () {
    $intake = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Closed->value);

    $this->get(route('portal.registration.maintenance'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/registration/RegistrationMaintenance')
            ->where('status', IntakePeriodStatusEnum::Closed->value)
            ->where('message', fn ($message) => str_contains($message, $intake->name)
                && str_contains($message, 'has closed')
                && ! str_contains($message, 'scheduled system maintenance'))
        );
});

test('login redirects new student to maintenance when registration is closed', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Suspended->value);

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->make([
        'tenant_id' => $tenant->id,
        'email_verified_at' => now(),
    ]);
    $user->password = 'Password1!';
    $user->save();
    $user->assignRole(RoleEnum::STUDENT->name());

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'Password1!',
    ])->assertRedirect(route('portal.registration.maintenance'));
});

test('intake period status can be updated from institution settings', function () {
    $intake = ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    $user = User::factory()->create(['tenant_id' => $intake->tenant_id]);

    Permission::findOrCreate('update:institution-settings', 'web');
    $user->givePermissionTo('update:institution-settings');

    $this->actingAs($user)
        ->put(route('intake-periods.update', $intake->id), [
            'name' => $intake->name,
            'start_date' => $intake->start_date,
            'end_date' => $intake->end_date,
            'description' => $intake->description,
            'status' => IntakePeriodStatusEnum::Suspended->value,
        ])
        ->assertSuccessful();

    expect($intake->fresh()->status)->toBe(IntakePeriodStatusEnum::Suspended);
});
