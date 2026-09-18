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
use Spatie\Permission\PermissionRegistrar;

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
        RoleEnum::DEAN->name() => 'confirm:hostel-payments',
        RoleEnum::WARDEN->name() => 'viewOnlyOwnHostel:hostels',
        RoleEnum::IT_SUPPORT_TECHNICIAN->name() => 'manage:data-maintenance',
    ];

    foreach ($expectations as $roleName => $permission) {
        $role = Role::query()->where('name', $roleName)->firstOrFail();
        expect($role->permissions->pluck('name')->all())->toContain($permission);
    }
});

test('it support technician pack is read-only for hms and excludes root manage', function () {
    $role = Role::query()->where('name', RoleEnum::IT_SUPPORT_TECHNICIAN->name())->firstOrFail();
    $permissions = $role->permissions->pluck('name')->all();

    expect($permissions)->toContain('viewAny:students')
        ->and($permissions)->toContain('view:students')
        ->and($permissions)->toContain('viewAny:users')
        ->and($permissions)->toContain('view:users')
        ->and($permissions)->toContain('update:users')
        ->and($permissions)->toContain('view:dashboards')
        ->and($permissions)->toContain('view-hostel:dashboards')
        ->and($permissions)->toContain('viewAny:hostels')
        ->and($permissions)->toContain('view:hostels')
        ->and($permissions)->toContain('manage:data-maintenance')
        ->and($permissions)->not->toContain('root:manage')
        ->and($permissions)->not->toContain('create:hostels')
        ->and($permissions)->not->toContain('update:hostels')
        ->and($permissions)->not->toContain('delete:hostels');
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

test('vice principal academics pack includes missing marks report permissions', function () {
    expect(PermissionHelper::vpAcademicsPermissions())->toContain('view:missing-marks-report')
        ->and(PermissionHelper::vpAcademicsPermissions())->toContain('export:missing-marks-report')
        ->and(PermissionHelper::vpAcademicsPermissions())->toContain('escalate:missing-marks')
        ->and(PermissionHelper::vpAcademicsPermissions())->toContain('remind:missing-marks');
});

test('vice principal academics pack includes class list confirmation', function () {
    expect(PermissionHelper::vpAcademicsPermissions())->toContain('confirm:class-lists')
        ->and(PermissionHelper::vpAcademicsPermissions())->toContain('manage-final:class-lists');
});

test('hod pack manages department assessment calendars, extensions and lecturers in charge but cannot approve beyond the global deadline', function () {
    $permissions = PermissionHelper::hodPermissions();

    expect($permissions)->toContain('create:department-assessment-calendar')
        ->and($permissions)->toContain('update:department-assessment-calendar')
        ->and($permissions)->toContain('captureForOthers:course-work')
        ->and($permissions)->toContain('approve:course-work-extensions')
        ->and($permissions)->toContain('assign:lecturer-in-charge')
        ->and($permissions)->toContain('acknowledge:course-work-progress-reports')
        ->and($permissions)->toContain('view:missing-marks-report')
        ->and($permissions)->not->toContain('approveBeyondGlobal:course-work-extensions')
        ->and($permissions)->not->toContain('updateClosed:assessment-calendar')
        ->and($permissions)->not->toContain('escalate:missing-marks');
});

test('lecturer pack can request extensions but cannot capture for others or approve', function () {
    $permissions = PermissionHelper::lecturerPermissions();

    expect($permissions)->toContain('request:course-work-extensions')
        ->and($permissions)->not->toContain('captureForOthers:course-work')
        ->and($permissions)->not->toContain('approve:course-work-extensions')
        ->and($permissions)->not->toContain('submit:course-work-progress-reports');
});

test('lecturer in charge role is seeded with progress reporting on top of the lecturer pack', function () {
    $role = Role::query()->where('name', RoleEnum::LECTURER_IN_CHARGE->name())->firstOrFail();
    $permissions = $role->permissions->pluck('name')->all();

    expect($permissions)->toContain('create:course-work')
        ->and($permissions)->toContain('view:course-work-progress')
        ->and($permissions)->toContain('submit:course-work-progress-reports')
        ->and($permissions)->not->toContain('captureForOthers:course-work')
        ->and($permissions)->not->toContain('assign:lecturer-in-charge');
});

test('vice principal academics pack can approve extensions beyond the global deadline and edit closed calendars', function () {
    $permissions = PermissionHelper::vpAcademicsPermissions();

    expect($permissions)->toContain('approveBeyondGlobal:course-work-extensions')
        ->and($permissions)->toContain('updateClosed:assessment-calendar')
        ->and($permissions)->toContain('captureForOthers:course-work')
        ->and($permissions)->not->toContain('create:department-assessment-calendar');
});

test('coursework window permission migration grants every new permission to the super user', function () {
    $newPermissions = [
        'updateClosed:assessment-calendar',
        'viewAny:department-assessment-calendar',
        'view:department-assessment-calendar',
        'create:department-assessment-calendar',
        'update:department-assessment-calendar',
        'delete:department-assessment-calendar',
        'restore:department-assessment-calendar',
        'forceDelete:department-assessment-calendar',
        'viewAuditTrail:department-assessment-calendar',
        'captureForOthers:course-work',
        'request:course-work-extensions',
        'viewAny:course-work-extensions',
        'approve:course-work-extensions',
        'approveBeyondGlobal:course-work-extensions',
        'revoke:course-work-extensions',
        'assign:lecturer-in-charge',
        'view:course-work-progress',
        'submit:course-work-progress-reports',
        'acknowledge:course-work-progress-reports',
    ];

    $superUser = Role::query()->where('name', RoleEnum::SUPER_USER->name())->firstOrFail();
    $superUser->revokePermissionTo($newPermissions);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $migration = require database_path('migrations/2026_09_14_090000_grant_coursework_window_permissions.php');
    $migration->up();

    $superUser = $superUser->fresh();

    foreach ($newPermissions as $permission) {
        expect($superUser->hasPermissionTo($permission))->toBeTrue();
    }
});

test('roles table seeder grants integrations permissions to the super user', function () {
    $superUser = Role::query()->where('name', RoleEnum::SUPER_USER->name())->firstOrFail();

    expect($superUser->hasPermissionTo('view:integrations'))->toBeTrue()
        ->and($superUser->hasPermissionTo('view:payment-gateway-settings'))->toBeTrue()
        ->and($superUser->hasPermissionTo('update:payment-gateway-settings'))->toBeTrue()
        ->and($superUser->hasPermissionTo('view:payments-debug'))->toBeTrue()
        ->and($superUser->hasPermissionTo('update:payments-debug'))->toBeTrue();
});

test('roles table seeder grants console permissions to the super user', function () {
    $superUser = Role::query()->where('name', RoleEnum::SUPER_USER->name())->firstOrFail();

    expect($superUser->hasPermissionTo('view:console'))->toBeTrue()
        ->and($superUser->hasPermissionTo('run:console-commands'))->toBeTrue()
        ->and($superUser->hasPermissionTo('run:destructive-console-commands'))->toBeTrue();
});

test('user access scope reaches only own departments for department scoped users', function () {
    $tenantId = TenantEnum::HARARE_POLY->id();
    $user = User::factory()->create(['tenant_id' => $tenantId]);
    $staff = createStaffForRolePermissionTest($user);

    Permission::findOrCreate('viewOnlyOwnDepartment:departments', 'web');
    $user->givePermissionTo('viewOnlyOwnDepartment:departments');

    $ownDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenantId,
        'department_id' => Department::query()->create(['name' => 'Own '.uniqid(), 'is_academic' => true])->id,
        'department_code' => 'OWN',
    ]);
    $otherDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenantId,
        'department_id' => Department::query()->create(['name' => 'Other '.uniqid(), 'is_academic' => true])->id,
        'department_code' => 'OTH',
    ]);
    $staff->institutionDepartments()->attach($ownDepartment->id);

    $scope = UserAccessScope::for($user->fresh());
    $collegeScope = UserAccessScope::for(User::factory()->create(['tenant_id' => $tenantId]));

    expect($scope->canReachDepartment((int) $ownDepartment->id))->toBeTrue()
        ->and($scope->canReachDepartment((int) $otherDepartment->id))->toBeFalse()
        ->and($scope->canReachDepartment(0))->toBeFalse()
        ->and($collegeScope->canReachDepartment((int) $otherDepartment->id))->toBeTrue()
        ->and((new UserAccessScope(null))->canReachDepartment((int) $ownDepartment->id))->toBeFalse();
});

test('hod permissions pack includes department metadata management', function () {
    $permissions = PermissionHelper::hodPermissions();

    expect($permissions)->toContain('viewOnlyOwnDepartment:departments')
        ->and($permissions)->toContain('update:department-metadata')
        ->and($permissions)->toContain('department-setup:courses');
});
