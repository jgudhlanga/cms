<?php

use App\Enums\Acl\RoleEnum;
use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\Acl\Role;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Users\User;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    RateLimiter::clear('api');
    Role::findOrCreate(RoleEnum::STUDENT->value, 'web');
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
});

function createGuestEnrollmentStudent(string $idNumber, ?string $studentNumber = null): Student
{
    $tenantId = TenantEnum::HARARE_POLY->id();
    $title = Title::query()->firstOrCreate(['name' => 'Mr Guest']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male Guest']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single Guest']);
    $idType = IdType::query()->firstOrCreate(['name' => 'National ID Guest']);

    $user = User::factory()->create([
        'tenant_id' => $tenantId,
        'first_name' => 'Tendai',
        'last_name' => 'Moyo',
        'email' => 'tendai.moyo.'.uniqid().'@example.com',
    ]);
    $user->assignRole(RoleEnum::STUDENT);

    return Student::query()->create([
        'tenant_id' => $tenantId,
        'user_id' => $user->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'id_type_id' => $idType->id,
        'id_number' => $idNumber,
        'student_number' => $studentNumber,
        'date_of_birth' => '2000-01-01',
    ]);
}

test('guest national id check returns not found for new identity', function () {
    $response = $this->postJson('/api/v1/guest/enrollment/check-national-id', [
        'id_number' => '63-1234567N63',
    ]);

    $response->assertOk()
        ->assertJson([
            'found' => false,
            'maskedName' => null,
            'loginEmail' => null,
        ]);
});

test('guest national id check returns masked record when duplicate exists', function () {
    createGuestEnrollmentStudent('63-1234567G63');

    $response = $this->postJson('/api/v1/guest/enrollment/check-national-id', [
        'id_number' => '63-1234567G63',
    ]);

    $response->assertOk()
        ->assertJson([
            'found' => true,
        ])
        ->assertJsonStructure(['maskedName', 'maskedEmail', 'loginEmail']);

    expect($response->json('maskedName'))->toContain('*');
});

test('guest returning lookup finds student by student number', function () {
    createGuestEnrollmentStudent('63-9999999H63', '25ENG0123HP');

    $response = $this->postJson('/api/v1/guest/enrollment/lookup', [
        'type' => 'student_number',
        'value' => '25ENG0123HP',
    ]);

    $response->assertOk()->assertJson(['found' => true]);
});

test('guest returning lookup validates national id format', function () {
    $response = $this->postJson('/api/v1/guest/enrollment/lookup', [
        'type' => 'id_number',
        'value' => 'invalid-id',
    ]);

    $response->assertUnprocessable();
});

test('portal store rejects invalid national id format', function () {
    $response = $this->post(route('portal.store'), [
        'registration_path' => 'zimbabwean',
        'first_name' => 'New',
        'last_name' => 'Applicant',
        'email' => 'invalid.format.'.uniqid().'@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
        'id_number' => 'not-a-valid-id',
        'acknowledged_advert' => true,
    ]);

    $response->assertSessionHasErrors('id_number');
});

test('portal store rejects duplicate national id registration', function () {
    createGuestEnrollmentStudent('44-0111222A44');

    $response = $this->post(route('portal.store'), [
        'registration_path' => 'zimbabwean',
        'first_name' => 'New',
        'last_name' => 'Applicant',
        'email' => 'new.applicant.'.uniqid().'@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
        'id_number' => '44-0111222A44',
        'acknowledged_advert' => true,
    ]);

    $response->assertSessionHasErrors('id_number');
});

test('portal store requires instruction acknowledgments', function () {
    $response = $this->post(route('portal.store'), [
        'registration_path' => 'zimbabwean',
        'first_name' => 'New',
        'last_name' => 'Applicant',
        'email' => 'missing.ack.'.uniqid().'@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
        'id_number' => '44-0888777C44',
    ]);

    $response->assertSessionHasErrors(['acknowledged_advert']);
});

test('portal store rejects weak password', function () {
    $response = $this->post(route('portal.store'), [
        'registration_path' => 'zimbabwean',
        'first_name' => 'New',
        'last_name' => 'Applicant',
        'email' => 'weak.password.'.uniqid().'@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'id_number' => '44-0777666D44',
        'acknowledged_advert' => true,
    ]);

    $response->assertSessionHasErrors('password');
});

test('portal store creates account and redirects to level selection for new zimbabwean', function () {
    $email = 'fresh.student.'.uniqid().'@example.com';

    $response = $this->post(route('portal.store'), [
        'registration_path' => 'zimbabwean',
        'first_name' => 'Fresh',
        'middle_name' => 'Middle',
        'last_name' => 'Student',
        'email' => $email,
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
        'id_number' => '44-0999888B44',
        'acknowledged_advert' => true,
    ]);

    $response->assertRedirect(route('portal.application.track'));
    $this->assertAuthenticated();
    expect(session('registration.id_number'))->toBe('44-0999888B44');
    expect(auth()->user()?->middle_name)->toBe('Middle');
    expect(auth()->user()?->registration_instructions_acknowledged_at)->not->toBeNull();
    $this->assertDatabaseHas('users', [
        'email' => $email,
        'first_name' => 'Fresh',
        'middle_name' => 'Middle',
        'last_name' => 'Student',
    ]);
});

test('portal level options page renders for newly registered student', function () {
    $tenantId = TenantEnum::HARARE_POLY->id();

    Level::factory()->create([
        'show_on_current_application_period' => true,
    ]);

    IntakePeriod::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Test Intake '.uniqid(),
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
        'calendar_year' => '2026/2027',
        'is_active' => true,
    ]);

    $email = 'level.page.'.uniqid().'@example.com';

    $this->post(route('portal.store'), [
        'registration_path' => 'zimbabwean',
        'first_name' => 'Level',
        'last_name' => 'Page',
        'email' => $email,
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
        'id_number' => '44-0555444E44',
        'acknowledged_advert' => true,
    ])->assertRedirect(route('portal.application.track'));

    $this->post(route('portal.application.select-track'), ['track' => 'regular'])
        ->assertRedirect(route('portal.application.level-options'));

    $response = $this->get(route('portal.application.level-options'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('portal/application/SelectLevelOption')
        ->has('levels')
        ->has('openLevelCount')
        ->has('hasActiveIntakes')
        ->where('availabilityIssue', null)
    );
});

test('portal level options reports no open levels when none are configured', function () {
    Level::query()->update(['show_on_current_application_period' => false]);

    $tenant = TenantEnum::HARARE_POLY->id();
    $user = User::factory()->create([
        'tenant_id' => $tenant,
        'email_verified_at' => now(),
    ]);
    $user->assignRole(RoleEnum::STUDENT);

    $response = $this->actingAs($user)
        ->withSession(['application.track' => 'regular'])
        ->get(route('portal.application.level-options'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('portal/application/SelectLevelOption')
        ->where('openLevelCount', 0)
        ->where('availabilityIssue', 'no_open_levels')
    );
});

test('student without profile can access portal level options', function () {
    $tenant = TenantEnum::HARARE_POLY->id();
    $user = User::factory()->create([
        'tenant_id' => $tenant,
        'email_verified_at' => now(),
    ]);
    $user->assignRole(RoleEnum::STUDENT);

    $response = $this->actingAs($user)
        ->withSession(['application.track' => 'regular'])
        ->get(route('portal.application.level-options'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('portal/application/SelectLevelOption')
    );
});

test('guest registration page includes intake metadata when intakes are open', function () {
    $tenantId = TenantEnum::HARARE_POLY->id();
    $intakeName = 'Guest Intake '.uniqid();

    IntakePeriod::query()->update(['is_active' => false]);

    IntakePeriod::query()->create([
        'tenant_id' => $tenantId,
        'name' => $intakeName,
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
        'calendar_year' => '2026/2027',
        'is_active' => true,
    ]);

    $this->get(route('portal.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/guest/RegistrationUserForm')
            ->has('openIntakePeriods')
            ->where('singleIntakeName', $intakeName)
        );
});

test('create application page includes intake name', function () {
    $tenantId = TenantEnum::HARARE_POLY->id();
    $intakeName = 'Apply Intake '.uniqid();

    IntakePeriod::query()->update(['is_active' => false]);

    IntakePeriod::query()->create([
        'tenant_id' => $tenantId,
        'name' => $intakeName,
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->addYears(10)->toDateString(),
        'calendar_year' => '2026/2027',
        'is_active' => true,
        'status' => IntakePeriodStatusEnum::Open,
        'is_continuous' => false,
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenantId,
        'email_verified_at' => now(),
    ]);
    $user->assignRole(RoleEnum::STUDENT);
    $user->givePermissionTo('manageOwnStudentPersonalDetails:students');

    $this->actingAs($user)
        ->withSession(['application.track' => 'regular'])
        ->get(route('portal.application.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/application/CreateApplication')
            ->where('intakeName', $intakeName)
        );
});
