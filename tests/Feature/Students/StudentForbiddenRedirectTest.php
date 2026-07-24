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

test('student is redirected to portal dashboard when accessing staff dashboard', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->assignRole(RoleEnum::STUDENT->name());
    $user->givePermissionTo('viewOwnDashboard:students');

    Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'title_id' => DB::table('titles')->insertGetId([
            'name' => 'Mr Redirect',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'gender_id' => DB::table('genders')->insertGetId([
            'title' => 'Male Redirect',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'marital_status_id' => DB::table('marital_statuses')->insertGetId([
            'title' => 'Single Redirect',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'id_type_id' => DB::table('id_types')->insertGetId([
            'name' => 'National ID Redirect',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'date_of_birth' => '2000-01-01',
        'student_number' => 'REDIRECT-001',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('portal.dashboard'));
});

test('student without profile is redirected to application track chooser on forbidden staff page', function () {
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);

    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->assignRole(RoleEnum::STUDENT->name());

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('portal.application.track'));
});

test('student still receives forbidden response for api requests', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->assignRole(RoleEnum::STUDENT->name());

    Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'title_id' => DB::table('titles')->insertGetId([
            'name' => 'Mr Api',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'gender_id' => DB::table('genders')->insertGetId([
            'title' => 'Male Api',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'marital_status_id' => DB::table('marital_statuses')->insertGetId([
            'title' => 'Single Api',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'id_type_id' => DB::table('id_types')->insertGetId([
            'name' => 'National ID Api',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'date_of_birth' => '2000-01-01',
        'student_number' => 'API-REDIRECT-001',
    ]);

    $response = $this->actingAs($user)->getJson(route('dashboard'));

    $response->assertForbidden();
});

test('student still receives forbidden on portal routes they cannot access', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->assignRole(RoleEnum::STUDENT->name());

    Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'title_id' => DB::table('titles')->insertGetId([
            'name' => 'Mr Portal Forbidden',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'gender_id' => DB::table('genders')->insertGetId([
            'title' => 'Male Portal Forbidden',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'marital_status_id' => DB::table('marital_statuses')->insertGetId([
            'title' => 'Single Portal Forbidden',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'id_type_id' => DB::table('id_types')->insertGetId([
            'name' => 'National ID Portal Forbidden',
            'created_at' => now(),
            'updated_at' => now(),
        ]),
        'date_of_birth' => '2000-01-01',
        'student_number' => 'PORTAL-FORBIDDEN-001',
    ]);

    $response = $this->actingAs($user)->get(route('portal.dashboard'));

    $response->assertForbidden();
});
