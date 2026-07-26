<?php

use App\Enums\Rbac\RoleEnum;
use App\Enums\Rbac\ScopeLevelEnum;
use App\Enums\Shared\EmploymentTypeEnum;
use App\Enums\Shared\TenantEnum;
use App\Helpers\PermissionHelper;
use App\Models\HMS\Hostel;
use App\Models\Institution\Department;
use App\Models\Institution\Division;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Staff;
use App\Models\Rbac\Permission;
use App\Models\Rbac\Role;
use App\Models\Shared\EmploymentType;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Users\User;
use App\Policies\HMS\HostelPolicy;
use App\Support\Rbac\UserAccessScope;
use Database\Seeders\Rbac\PermissionsTableSeeder;
use Database\Seeders\Rbac\RoleGroupSeeder;
use Database\Seeders\Rbac\RolesTableSeeder;

beforeEach(function () {
    (new RoleGroupSeeder)->run();
    (new PermissionsTableSeeder)->run();
    (new RolesTableSeeder)->run();
});

function createStaffForRolePermissionTest(User $user): Staff
{
    return Staff::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'title_id' => Title::query()->firstOrCreate(['name' => 'Mr'])->id,
        'gender_id' => Gender::query()->firstOrCreate(['title' => 'Male'])->id,
        'marital_status_id' => MaritalStatus::query()->firstOrCreate(['title' => 'Single'])->id,
        'employment_type_id' => EmploymentType::query()->firstOrCreate([
            'name' => EmploymentTypeEnum::FULL_TIME->value,
        ], [
            'description' => EmploymentTypeEnum::FULL_TIME->description(),
        ])->id,
        'employee_number' => 'ROLE-TEST-'.uniqid(),
    ]);
}

test('roles table seeder syncs permission packs onto leadership roles', function () {
    $expectations = [
        RoleEnum::HEAD_OF_DEPARTMENT->name() => 'viewOnlyOwnDepartment:departments',
        RoleEnum::HEAD_OF_DIVISION->name() => 'view-academic:dashboards',
        RoleEnum::VICE_PRINCIPAL->name() => 'view-academic:dashboards',
        RoleEnum::VICE_PRINCIPAL_ADMIN->name() => 'view-finance:dashboards',
        RoleEnum::PRINCIPAL->name() => 'view-hostel:dashboards',
        RoleEnum::DEAN->name() => 'viewAny:hostels',
        RoleEnum::WARDEN->name() => 'viewOnlyOwnHostel:hostels',
    ];

    foreach ($expectations as $roleName => $permission) {
        $role = Role::query()->where('name', $roleName)->firstOrFail();
        expect($role->permissions->pluck('name')->all())->toContain($permission);
    }
});

test('vice principal academics pack excludes hostel and finance dashboard tabs', function () {
    $role = Role::query()->where('name', RoleEnum::VICE_PRINCIPAL->name())->firstOrFail();
    $permissions = $role->permissions->pluck('name')->all();

    expect($permissions)->toContain('view-academic:dashboards')
        ->and($permissions)->not->toContain('view-hostel:dashboards')
        ->and($permissions)->not->toContain('view-finance:dashboards');
});

test('vice principal academics pack hides settings and rbac but includes institution config access', function () {
    $role = Role::query()->where('name', RoleEnum::VICE_PRINCIPAL->name())->firstOrFail();
    $permissions = $role->permissions->pluck('name')->all();

    expect($permissions)->toContain('view:institution-settings')
        ->and($permissions)->toContain('viewAny:divisions')
        ->and($permissions)->toContain('viewAny:departments')
        ->and($permissions)->toContain('viewAny:intake-periods')
        ->and($permissions)->toContain('viewAny:assessment-types')
        ->and($permissions)->toContain('viewAny:document-templates')
        ->and($permissions)->toContain('create:document-templates')
        ->and($permissions)->toContain('update:document-templates')
        ->and($permissions)->not->toContain('view:settings')
        ->and($permissions)->not->toContain('create:institution-settings')
        ->and($permissions)->not->toContain('root:manage');
});

test('vp academics permissions helper matches seeded pack for settings and document templates', function () {
    $permissions = PermissionHelper::vpAcademicsPermissions();

    expect($permissions)->toContain('viewAny:document-templates')
        ->and($permissions)->toContain('viewAny:divisions')
        ->and($permissions)->not->toContain('view:settings')
        ->and($permissions)->not->toContain('root:manage');
});

test('vice principal academics display name is updated', function () {
    expect(RoleEnum::VICE_PRINCIPAL->name())->toBe('Vice Principal Academics')
        ->and(RoleEnum::VICE_PRINCIPAL->value)->toBe('vice-principal-academics')
        ->and(Role::query()->where('name', 'Vice Principal Academics')->exists())->toBeTrue();
});

test('user access scope resolves division departments for head of division', function () {
    $tenantId = TenantEnum::HARARE_POLY->id();
    $user = User::factory()->create(['tenant_id' => $tenantId]);
    $staff = createStaffForRolePermissionTest($user);

    Permission::findOrCreate('viewOnlyOwnDepartment:departments', 'web');
    $user->givePermissionTo('viewOnlyOwnDepartment:departments');

    $division = Division::query()->create([
        'name' => 'Engineering Division '.uniqid(),
        'head_of_division_id' => $staff->id,
    ]);

    $catalogDepartment = Department::query()->create([
        'name' => 'Dept '.uniqid(),
        'is_academic' => true,
    ]);

    $department = InstitutionDepartment::query()->create([
        'tenant_id' => $tenantId,
        'department_id' => $catalogDepartment->id,
        'division_id' => $division->id,
        'department_code' => 'ENG',
    ]);

    $this->actingAs($user);

    $scope = UserAccessScope::for($user->fresh());

    expect($scope->level())->toBe(ScopeLevelEnum::Division)
        ->and($scope->departmentIds())->toContain((int) $department->id);
});

test('hostel policy blocks warden from unassigned hostel', function () {
    $tenantId = TenantEnum::HARARE_POLY->id();
    $user = User::factory()->create(['tenant_id' => $tenantId]);
    $staff = createStaffForRolePermissionTest($user);

    foreach (['view:hostels', 'viewOnlyOwnHostel:hostels', 'update:hostels'] as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    $assigned = Hostel::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Assigned Hostel '.uniqid(),
        'location' => 'North',
        'floor_count' => 1,
        'rooms_count' => 1,
        'capacity' => 10,
        'status' => 'active',
        'type' => 'male',
        'warden_id' => $staff->id,
    ]);

    $other = Hostel::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Other Hostel '.uniqid(),
        'location' => 'South',
        'floor_count' => 1,
        'rooms_count' => 1,
        'capacity' => 10,
        'status' => 'active',
        'type' => 'female',
        'warden_id' => null,
    ]);

    $policy = new HostelPolicy;
    $user = $user->fresh();

    expect($policy->view($user, $assigned))->toBeTrue()
        ->and($policy->view($user, $other))->toBeFalse()
        ->and($policy->update($user, $assigned))->toBeTrue()
        ->and($policy->update($user, $other))->toBeFalse();
});

test('hod permissions pack includes department metadata management', function () {
    $permissions = PermissionHelper::hodPermissions();

    expect($permissions)->toContain('viewOnlyOwnDepartment:departments')
        ->and($permissions)->toContain('update:department-metadata')
        ->and($permissions)->toContain('department-setup:courses');
});
