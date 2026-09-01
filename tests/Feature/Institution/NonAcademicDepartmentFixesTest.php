<?php

declare(strict_types=1);

use App\Actions\Institution\DeduplicateInstitutionDepartmentsAction;
use App\Enums\Institution\DepartmentEnum;
use App\Enums\Rbac\RoleEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\Institution\Department;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Staff;
use App\Models\Rbac\Permission;
use App\Models\Rbac\Role;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Users\User;
use App\Support\Institution\DepartmentStaffRoles;
use Database\Seeders\Rbac\PermissionsTableSeeder;
use Database\Seeders\Rbac\RoleGroupSeeder;
use Database\Seeders\Rbac\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RoleGroupSeeder)->run();
    (new PermissionsTableSeeder)->run();
    (new RolesTableSeeder)->run();
});

function makeInstitutionDepartmentAdmin(): User
{
    $user = User::factory()->create(['tenant_id' => TenantEnum::HARARE_POLY->id()]);
    Permission::findOrCreate('view:department-metadata', 'web');
    Permission::findOrCreate('create:department-metadata', 'web');
    Permission::findOrCreate('update:department-metadata', 'web');
    $user->givePermissionTo([
        'view:department-metadata',
        'create:department-metadata',
        'update:department-metadata',
    ]);

    return $user;
}

function createNonAcademicInstitutionDepartment(string $name = 'Finance'): InstitutionDepartment
{
    $department = Department::query()->create([
        'name' => $name,
        'is_academic' => false,
    ]);

    return InstitutionDepartment::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'department_id' => $department->id,
        'department_code' => null,
        'color_code' => '#AABBCC',
    ]);
}

test('deduplicate institution departments merges staff onto the oldest row', function () {
    Schema::table('institution_departments', function ($table): void {
        $table->dropUnique('inst_dept_tenant_active_dept_unq');
    });

    $keeper = createNonAcademicInstitutionDepartment('Finance');
    $duplicate = InstitutionDepartment::query()->create([
        'tenant_id' => $keeper->tenant_id,
        'department_id' => $keeper->department_id,
        'department_code' => null,
        'color_code' => '#CCBBAA',
    ]);

    $staffUser = User::factory()->create(['tenant_id' => $keeper->tenant_id]);
    $staff = Staff::query()->create([
        'tenant_id' => $keeper->tenant_id,
        'user_id' => $staffUser->id,
        'title_id' => Title::query()->firstOrCreate(['name' => 'Mr'])->id,
        'gender_id' => Gender::query()->firstOrCreate(['title' => 'Male'])->id,
        'marital_status_id' => MaritalStatus::query()->firstOrCreate(['title' => 'Single'])->id,
        'employee_number' => 'FIN-'.uniqid(),
    ]);
    $staff->institutionDepartments()->attach($duplicate->id);

    $action = app(DeduplicateInstitutionDepartmentsAction::class);
    $plan = $action->plan();

    expect($plan)->toHaveCount(1)
        ->and($plan[0]['keeper_id'])->toBe($keeper->id)
        ->and($plan[0]['duplicate_ids'])->toBe([$duplicate->id]);

    $action->execute($plan);

    expect(InstitutionDepartment::withTrashed()->whereKey($duplicate->id)->exists())->toBeTrue()
        ->and($duplicate->fresh()->trashed())->toBeTrue()
        ->and($staff->fresh()->institutionDepartments()->pluck('institution_departments.id')->all())
        ->toContain($keeper->id);
});

test('institution departments index exposes catalog department ids for link modal prefill', function () {
    $user = makeInstitutionDepartmentAdmin();
    $linked = createNonAcademicInstitutionDepartment('Finance');

    $response = $this->actingAs($user)
        ->get(route('institution-departments.index', ['is_academic' => 0]))
        ->assertSuccessful();

    $prefillIds = collect($response->original->getData()['page']['props']['institutionDepartmentIds'])
        ->map(fn ($id) => (int) $id)
        ->all();

    expect($prefillIds)->toContain((int) $linked->department_id)
        ->and(
            collect($prefillIds)->every(
                fn (int $id): bool => Department::query()->whereKey($id)->exists(),
            ),
        )->toBeTrue();
});

test('syncing an already linked non academic department does not create a duplicate', function () {
    $user = makeInstitutionDepartmentAdmin();
    $linked = createNonAcademicInstitutionDepartment('Human Resources');

    $this->actingAs($user)
        ->post(route('institution-departments.sync'), [
            'is_academic' => false,
            'department_ids' => [$linked->department_id],
        ])
        ->assertSuccessful();

    expect(
        InstitutionDepartment::query()
            ->where('department_id', $linked->department_id)
            ->whereNull('deleted_at')
            ->count(),
    )->toBe(1);
});

test('department staff roles exclude super user group and include lecturer', function () {
    $finance = createNonAcademicInstitutionDepartment(DepartmentEnum::FINANCE->value);
    $finance->load('department');

    $slugs = DepartmentStaffRoles::allowedSlugsFor($finance);

    expect($slugs)->toContain(RoleEnum::BURSAR->value, RoleEnum::LECTURER->value)
        ->not->toContain(RoleEnum::SUPER_USER->value, RoleEnum::SUPER_ADMINISTRATOR->value);
});

test('department staff roles for academic departments include lecturer roles', function () {
    $department = Department::query()->create([
        'name' => 'Applied Arts',
        'is_academic' => true,
    ]);

    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => TenantEnum::HARARE_POLY->id(),
        'department_id' => $department->id,
        'department_code' => 'AA01',
        'color_code' => '#112233',
    ]);

    $institutionDepartment->load('department');

    $slugs = DepartmentStaffRoles::allowedSlugsFor($institutionDepartment);

    expect($slugs)->toContain(RoleEnum::LECTURER->value, RoleEnum::HEAD_OF_DIVISION->value);
});

test('staff create exposes all assignable role slugs', function () {
    $user = makeInstitutionDepartmentAdmin();
    $finance = createNonAcademicInstitutionDepartment(DepartmentEnum::FINANCE->value);

    $this->actingAs($user)
        ->get(route('staff.create', ['department' => $finance->id]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('institution/staff/Create')
            ->has('allowedRoleSlugs')
            ->where('allowedRoleSlugs', fn ($slugs) => collect($slugs)->contains(RoleEnum::BURSAR->value)
                && collect($slugs)->contains(RoleEnum::LECTURER->value)
                && ! collect($slugs)->contains(RoleEnum::SUPER_USER->value)));
});

test('staff store rejects super user role', function () {
    $user = makeInstitutionDepartmentAdmin();
    $finance = createNonAcademicInstitutionDepartment(DepartmentEnum::FINANCE->value);
    $superUserRole = Role::query()->where('slug', RoleEnum::SUPER_USER->value)->firstOrFail();

    $this->actingAs($user)
        ->post(route('staff.store', ['department' => $finance->id]), [
            'first_name' => 'Test',
            'last_name' => 'Finance',
            'employee_number' => 'FIN-STAFF-'.uniqid(),
            'gender_id' => Gender::query()->firstOrCreate(['title' => 'Male'])->id,
            'marital_status_id' => MaritalStatus::query()->firstOrCreate(['title' => 'Single'])->id,
            'title_id' => Title::query()->firstOrCreate(['name' => 'Mr'])->id,
            'email' => 'finance-staff-'.uniqid().'@example.com',
            'phone_number' => '0777000000',
            'date_of_birth' => '1990-01-01',
            'employment_type_id' => null,
            'role_ids' => [$superUserRole->id],
            'institution_department_id' => $finance->id,
        ])
        ->assertSessionHasErrors('role_ids');
});
