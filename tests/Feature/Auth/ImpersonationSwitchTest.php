<?php

use App\Enums\Rbac\RoleEnum;
use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Models\Rbac\Role;
use App\Models\Students\Student;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Role::findOrCreate(RoleEnum::STUDENT->name(), 'web');
});

function createImpersonatableStudentApplicant(): User
{
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->assignRole(RoleEnum::STUDENT->name());
    $user->givePermissionTo('viewOwnDashboard:students');

    return $user;
}

function createImpersonatableStudent(): User
{
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->assignRole(RoleEnum::STUDENT->name());
    $user->givePermissionTo([
        'viewOwnDashboard:students',
        'manageOwnStudentPersonalDetails:students',
    ]);

    Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'title_id' => DB::table('titles')->insertGetId([
            'name' => 'Mr Impersonate',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'gender_id' => DB::table('genders')->insertGetId([
            'title' => 'Male Impersonate',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'marital_status_id' => DB::table('marital_statuses')->insertGetId([
            'title' => 'Single Impersonate',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'id_type_id' => DB::table('id_types')->insertGetId([
            'name' => 'National ID Impersonate',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'date_of_birth' => '2000-01-01',
        'student_number' => 'IMP-'.uniqid(),
    ]);

    return $user;
}

function statefulApiHeaders(): array
{
    return ['Referer' => rtrim((string) config('app.url'), '/').'/portal/dashboard'];
}

function impersonatorUser(): User
{
    $impersonator = User::factory()->create();
    $impersonator->givePermissionTo('root:manage');

    return $impersonator;
}

test('authorized user can impersonate another user', function () {
    $impersonator = impersonatorUser();
    $targetUser = User::factory()->create();

    $response = $this
        ->actingAs($impersonator)
        ->get(route('impersonate', ['id' => $targetUser->id]));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($targetUser);
    expect(session()->has('impersonated_by'))->toBeTrue();
});

test('authorized user can switch impersonation without 403', function () {
    $impersonator = impersonatorUser();
    $firstTarget = User::factory()->create();
    $secondTarget = User::factory()->create();

    $this
        ->actingAs($impersonator)
        ->get(route('impersonate', ['id' => $firstTarget->id]))
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($firstTarget);

    $response = $this->get(route('impersonate', ['id' => $secondTarget->id]));

    $response->assertRedirect();
    $this->assertAuthenticatedAs($secondTarget);
});

test('users cannot impersonate themselves', function () {
    $impersonator = impersonatorUser();

    $this
        ->actingAs($impersonator)
        ->get(route('impersonate', ['id' => $impersonator->id]))
        ->assertForbidden();
});

test('admin impersonates staff user without redirecting to student portal', function () {
    $impersonator = impersonatorUser();
    $staffUser = User::factory()->create();

    $this
        ->actingAs($impersonator)
        ->get(route('impersonate', ['id' => $staffUser->id]))
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($staffUser);

    $dashboardResponse = $this->get(route('dashboard'));
    expect($dashboardResponse->headers->get('Location', ''))->not->toContain('portal/dashboard');

    $usersResponse = $this->get(route('users.index'));
    expect($usersResponse->headers->get('Location', ''))->not->toContain('portal/dashboard');

    $this->assertAuthenticatedAs($staffUser);
});

test('admin impersonates student applicant and redirects to application track chooser', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);

    $impersonator = impersonatorUser();
    $applicantUser = createImpersonatableStudentApplicant();

    $this
        ->actingAs($impersonator)
        ->get(route('impersonate', ['id' => $applicantUser->id]))
        ->assertRedirect(route('portal.application.track'));

    $this->assertAuthenticatedAs($applicantUser);
});

test('admin impersonates student with profile and redirects to portal dashboard', function () {
    $impersonator = impersonatorUser();
    $studentUser = createImpersonatableStudent();

    $this
        ->actingAs($impersonator)
        ->get(route('impersonate', ['id' => $studentUser->id]))
        ->assertRedirect(route('portal.dashboard'));

    $this->assertAuthenticatedAs($studentUser);
    expect(session()->has('impersonated_by'))->toBeTrue();
});

test('dashboard stats api works while impersonating a student', function () {
    $impersonator = impersonatorUser();
    $studentUser = createImpersonatableStudent();

    $this
        ->actingAs($impersonator)
        ->get(route('impersonate', ['id' => $studentUser->id]))
        ->assertRedirect(route('portal.dashboard'));

    $this
        ->jsonApi('student-applications')
        ->withHeaders(statefulApiHeaders())
        ->get(route('v1.json.student-applications.dashboardStats'))
        ->assertSuccessful()
        ->assertJsonPath('meta.applicationCount', 0);

    $this->assertAuthenticatedAs($studentUser);
});

test('portal navigation remains authenticated while impersonating', function () {
    $impersonator = impersonatorUser();
    $studentUser = createImpersonatableStudent();

    $this
        ->actingAs($impersonator)
        ->get(route('impersonate', ['id' => $studentUser->id]))
        ->assertRedirect(route('portal.dashboard'));

    $this
        ->jsonApi('student-applications')
        ->withHeaders(statefulApiHeaders())
        ->get(route('v1.json.student-applications.dashboardStats'))
        ->assertSuccessful();

    $this
        ->get(route('portal.profile.personal-information'))
        ->assertOk();

    $this->assertAuthenticatedAs($studentUser);
});

test('leaving impersonation restores the admin session', function () {
    $impersonator = impersonatorUser();
    $studentUser = createImpersonatableStudent();

    $this
        ->actingAs($impersonator)
        ->get(route('impersonate', ['id' => $studentUser->id]))
        ->assertRedirect(route('portal.dashboard'));

    $this
        ->get(route('impersonate.leave'))
        ->assertRedirect(route('users.index'));

    $this->assertAuthenticatedAs($impersonator);
    expect(session()->has('impersonated_by'))->toBeFalse();
});

test('admin users cannot be impersonated', function () {
    $impersonator = impersonatorUser();
    $otherAdmin = User::factory()->create();
    $otherAdmin->givePermissionTo('root:manage');

    expect($otherAdmin->canBeImpersonated())->toBeFalse();

    $this
        ->actingAs($impersonator)
        ->get(route('impersonate', ['id' => $otherAdmin->id]))
        ->assertRedirect();

    $this->assertAuthenticatedAs($impersonator);
});

test('credential route is blocked while impersonating', function () {
    $impersonator = impersonatorUser();
    $studentUser = createImpersonatableStudent();

    $this
        ->actingAs($impersonator)
        ->get(route('impersonate', ['id' => $studentUser->id]))
        ->assertRedirect(route('portal.dashboard'));

    $this
        ->from(route('portal.dashboard'))
        ->get(route('portal.profile.authentication'))
        ->assertRedirect(route('portal.dashboard'));
});

test('admin routes redirect to portal while impersonating', function () {
    $impersonator = impersonatorUser();
    $studentUser = createImpersonatableStudent();

    $this
        ->actingAs($impersonator)
        ->get(route('impersonate', ['id' => $studentUser->id]))
        ->assertRedirect(route('portal.dashboard'));

    $this
        ->get(route('users.index'))
        ->assertRedirect(route('portal.dashboard'));

    $this->assertAuthenticatedAs($studentUser);
});
