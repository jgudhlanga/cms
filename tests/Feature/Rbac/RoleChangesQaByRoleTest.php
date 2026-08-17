<?php

/**
 * Role Changes Test Plan — automated coverage for phases 1–10.
 * Complements RolePermissionPacksTest (phase 0 foundation).
 */

use App\Enums\Rbac\RoleEnum;
use App\Enums\Rbac\ScopeLevelEnum;
use App\Enums\Shared\EmploymentTypeEnum;
use App\Enums\Shared\TenantEnum;
use App\Helpers\PermissionHelper;
use App\Http\Resources\Institution\DivisionResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Models\HMS\Hostel;
use App\Models\Institution\Department;
use App\Models\Institution\Division;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Staff;
use App\Models\Rbac\Role;
use App\Models\Shared\EmploymentType;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Users\User;
use App\Policies\HMS\HostelPolicy;
use App\Services\AcademicCalendars\CourseWorkAssessmentLockService;
use App\Services\Dashboard\DashboardModuleService;
use App\Support\Rbac\UserAccessScope;
use Database\Seeders\Rbac\PermissionsTableSeeder;
use Database\Seeders\Rbac\RoleGroupSeeder;
use Database\Seeders\Rbac\RolesTableSeeder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    (new RoleGroupSeeder)->run();
    (new PermissionsTableSeeder)->run();
    (new RolesTableSeeder)->run();
    enableDashboardModule();
});

function qaStaff(User $user): Staff
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
        'employee_number' => 'QA-'.uniqid(),
    ]);
}

function qaUserWithRole(RoleEnum $role): User
{
    $user = User::factory()->create(['tenant_id' => TenantEnum::HARARE_POLY->id()]);
    $roleModel = Role::query()->where('name', $role->name())->firstOrFail();
    $user->syncRoles([$roleModel]);

    return $user->fresh();
}

function qaVisibleTabs(User $user): array
{
    return app(DashboardModuleService::class)->visibleTabsFor($user->fresh());
}

// --- Phase 1: Org hierarchy ---

test('phase1 division resource and fillable support head of division', function () {
    $user = User::factory()->create(['tenant_id' => TenantEnum::HARARE_POLY->id()]);
    $staff = qaStaff($user);

    $division = Division::query()->create([
        'name' => 'QA Division '.uniqid(),
        'head_of_division_id' => $staff->id,
        'description' => 'QA',
    ]);

    expect($division->fresh()->head_of_division_id)->toBe($staff->id)
        ->and($division->headOfDivision)->not->toBeNull();

    $request = Request::create('/institution/config/divisions', 'GET');
    $request->setRouteResolver(fn () => tap(new class
    {
        public function named(...$patterns): bool
        {
            return true;
        }
    }, fn () => null));

    // Resource includes head fields without needing named route match for core attrs
    $payload = (new DivisionResource($division->fresh()->load('headOfDivision.user')))->toArray(Request::create('/'));

    expect($payload['attributes']['headOfDivisionId'])->toBe($staff->id)
        ->and($payload['attributes'])->toHaveKey('headOfDivision');
});

test('phase1 institution department can link to division and resource exposes it', function () {
    $tenantId = TenantEnum::HARARE_POLY->id();
    $division = Division::query()->create(['name' => 'Link Div '.uniqid()]);
    $catalog = Department::query()->create(['name' => 'Link Dept '.uniqid(), 'is_academic' => true]);

    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenantId,
        'department_id' => $catalog->id,
        'department_code' => 'LD',
        'division_id' => $division->id,
    ]);

    expect($institutionDepartment->fresh()->division_id)->toBe($division->id);

    $payload = (new InstitutionDepartmentResource($institutionDepartment->fresh()))->toArray(Request::create('/'));

    expect($payload['attributes']['divisionId'])->toBe($division->id)
        ->and($payload['attributes']['division'])->toBe($division->name);
});

test('phase1 ui files include head of division combobox and division column', function () {
    $createEdit = file_get_contents(resource_path('js/pages/institution/dropdowns/divisions/partials/CreateEdit.vue'));
    $columns = file_get_contents(resource_path('js/composables/institution/useInstitutionDepartments.ts'));
    $editDivision = file_get_contents(resource_path('js/pages/institution/departments/partials/EditDepartmentDivision.vue'));

    expect($createEdit)->toContain('head_of_division_id')
        ->and($createEdit)->toContain('BaseCombobox')
        ->and($columns)->toContain("trans_choice('trans.division'")
        ->and($editDivision)->toContain('division_id');
});

// --- Phase 2: Lecturer ---

test('phase2 lecturer sees teaching academic tab and lecturer abilities', function () {
    $user = qaUserWithRole(RoleEnum::LECTURER);
    $tabs = qaVisibleTabs($user);
    $permissions = $user->getAllPermissions()->pluck('name')->all();

    expect($tabs)->toContain('academic')
        ->and($tabs)->not->toContain('hostel')
        ->and($tabs)->not->toContain('finance')
        ->and($permissions)->toContain('view:lecturer-dashboard')
        ->and($permissions)->toContain('view:lecturer-classes')
        ->and($permissions)->toContain('view:lecturer-modules')
        ->and($permissions)->toContain('update:course-work');
});

// --- Phase 3: HOD ---

test('phase3 hod has own-department scope and academic enrolment tabs', function () {
    $user = qaUserWithRole(RoleEnum::HEAD_OF_DEPARTMENT);
    $tabs = qaVisibleTabs($user);
    $permissions = $user->getAllPermissions()->pluck('name')->all();

    expect($permissions)->toContain('viewOnlyOwnDepartment:departments')
        ->and($permissions)->toContain('update:department-metadata')
        ->and($permissions)->toContain('department-setup:courses')
        ->and($tabs)->toContain('academic')
        ->and($tabs)->toContain('enrolments')
        ->and($tabs)->not->toContain('hostel');
});

// --- Phase 4: HoDiv ---

test('phase4 head of division scopes metrics to division departments', function () {
    $user = qaUserWithRole(RoleEnum::HEAD_OF_DIVISION);
    $staff = qaStaff($user);

    $division = Division::query()->create([
        'name' => 'HoDiv Scope '.uniqid(),
        'head_of_division_id' => $staff->id,
    ]);

    $inDivision = InstitutionDepartment::query()->create([
        'tenant_id' => $user->tenant_id,
        'department_id' => Department::query()->create(['name' => 'In '.uniqid(), 'is_academic' => true])->id,
        'department_code' => 'IN',
        'division_id' => $division->id,
    ]);

    $outside = InstitutionDepartment::query()->create([
        'tenant_id' => $user->tenant_id,
        'department_id' => Department::query()->create(['name' => 'Out '.uniqid(), 'is_academic' => true])->id,
        'department_code' => 'OUT',
    ]);

    $this->actingAs($user->fresh());
    $scope = UserAccessScope::for($user->fresh());

    expect($scope->level())->toBe(ScopeLevelEnum::Division)
        ->and($scope->departmentIds())->toContain((int) $inDivision->id)
        ->and($scope->departmentIds())->not->toContain((int) $outside->id);

    $tabs = qaVisibleTabs($user);
    expect($tabs)->toContain('academic')
        ->and($tabs)->toContain('enrolments');
});

// --- Phase 5: VP Academics ---

test('phase5 vp academics has academic tabs and excludes hostel and finance', function () {
    $user = qaUserWithRole(RoleEnum::VICE_PRINCIPAL);
    $tabs = qaVisibleTabs($user);
    $permissions = $user->getAllPermissions()->pluck('name')->all();

    expect(RoleEnum::VICE_PRINCIPAL->name())->toBe('Vice Principal Academics')
        ->and($tabs)->toContain('overview')
        ->and($tabs)->toContain('academic')
        ->and($tabs)->toContain('enrolments')
        ->and($tabs)->toContain('staff')
        ->and($tabs)->not->toContain('hostel')
        ->and($tabs)->not->toContain('finance')
        ->and($permissions)->toContain('toggle:coursework-capture')
        ->and($permissions)->toContain('view:institution-settings')
        ->and($permissions)->toContain('viewAny:divisions')
        ->and($permissions)->toContain('viewAny:departments')
        ->and($permissions)->toContain('viewAny:intake-periods')
        ->and($permissions)->toContain('viewAny:assessment-types')
        ->and($permissions)->toContain('viewAny:document-templates')
        ->and($permissions)->not->toContain('view:settings')
        ->and($permissions)->not->toContain('root:manage')
        ->and($permissions)->not->toContain('view-hostel:dashboards')
        ->and($permissions)->not->toContain('view-finance:dashboards');
});

test('phase5 vp academics can open institution config pages but not settings or rbac', function () {
    $user = qaUserWithRole(RoleEnum::VICE_PRINCIPAL);

    $this->actingAs($user)->get(route('intake-periods.index'))->assertSuccessful();
    $this->actingAs($user)->get(route('departments.index'))->assertSuccessful();
    $this->actingAs($user)->get(route('divisions.index'))->assertSuccessful();
    $this->actingAs($user)->get(route('assessment-types.index'))->assertSuccessful();
    $this->actingAs($user)->get(route('document-templates.index'))->assertSuccessful();
    $this->actingAs($user)->get(route('settings.index'))->assertForbidden();
    $this->actingAs($user)->get(route('rbac.index'))->assertForbidden();
});

test('phase5 sidebar nests institution config items under institution config parent', function () {
    $sidebar = file_get_contents(resource_path('js/composables/core/useSidebarMenu.ts'));

    expect($sidebar)->toContain('trans.institution_config')
        ->and($sidebar)->toContain("route('institution.setup')")
        ->and($sidebar)->toContain("route('intake-periods.index')")
        ->and($sidebar)->toContain("route('document-templates.index')")
        ->and($sidebar)->toContain("route('departments.index')")
        ->and($sidebar)->toContain("route('divisions.index')")
        ->and($sidebar)->toContain("route('assessment-types.index')")
        ->and($sidebar)->toContain("canShowMenuItem('root:manage', 'root', moduleState)");
});

// --- Phase 6: VP Admin ---

test('phase6 vp admin has finance and hostel dashboards', function () {
    $user = qaUserWithRole(RoleEnum::VICE_PRINCIPAL_ADMIN);
    $tabs = qaVisibleTabs($user);
    $permissions = $user->getAllPermissions()->pluck('name')->all();

    expect($tabs)->toContain('finance')
        ->and($tabs)->toContain('hostel')
        ->and($permissions)->toContain('viewAny:finances')
        ->and($permissions)->toContain('viewAny:hostels');
});

test('phase6 finance tab ui is wired to financeDashboard not hardcoded usd demo', function () {
    $financeTab = file_get_contents(resource_path('js/pages/dashboard/tabs/FinanceTab.vue'));

    expect($financeTab)->toContain('financeDashboard')
        ->and($financeTab)->not->toContain('USD 714k');
});

// --- Phase 7: Principal ---

test('phase7 principal sees academic finance and hostel composite tabs', function () {
    $user = qaUserWithRole(RoleEnum::PRINCIPAL);
    $tabs = qaVisibleTabs($user);

    expect($tabs)->toContain('academic')
        ->and($tabs)->toContain('enrolments')
        ->and($tabs)->toContain('finance')
        ->and($tabs)->toContain('hostel')
        ->and($user->can('update:users'))->toBeTrue()
        ->and($user->can('toggle:coursework-capture'))->toBeTrue();
});

// --- Phase 8: Dean ---

test('phase8 dean has hostel dashboard and full hostel management abilities', function () {
    $user = qaUserWithRole(RoleEnum::DEAN);
    $tabs = qaVisibleTabs($user);
    $permissions = $user->getAllPermissions()->pluck('name')->all();

    expect($tabs)->toContain('hostel')
        ->and($tabs)->not->toContain('academic')
        ->and($permissions)->toContain('create:hostels')
        ->and($permissions)->toContain('update:hostels')
        ->and($permissions)->toContain('viewAny:hostel-applications')
        ->and($permissions)->toContain('import:hostel-applications')
        ->and($permissions)->toContain('confirm:hostel-payments')
        ->and($permissions)->not->toContain('viewOnlyOwnHostel:hostels');

    $policy = new HostelPolicy;
    $hostel = Hostel::query()->create([
        'tenant_id' => $user->tenant_id,
        'name' => 'Dean Hostel '.uniqid(),
        'location' => 'North',
        'floor_count' => 1,
        'rooms_count' => 1,
        'capacity' => 10,
        'status' => 'active',
        'type' => 'male',
        'warden_id' => null,
    ]);

    expect($policy->view($user, $hostel))->toBeTrue()
        ->and($policy->update($user, $hostel))->toBeTrue();
});

// --- Phase 9: Warden ---

test('phase9 warden is scoped to assigned hostel only', function () {
    $user = qaUserWithRole(RoleEnum::WARDEN);
    $staff = qaStaff($user);
    $tabs = qaVisibleTabs($user);

    expect($tabs)->toContain('hostel')
        ->and($user->can('viewOnlyOwnHostel:hostels'))->toBeTrue();

    $assigned = Hostel::query()->create([
        'tenant_id' => $user->tenant_id,
        'name' => 'Warden Assigned '.uniqid(),
        'location' => 'North',
        'floor_count' => 1,
        'rooms_count' => 1,
        'capacity' => 10,
        'status' => 'active',
        'type' => 'male',
        'warden_id' => $staff->id,
    ]);

    $other = Hostel::query()->create([
        'tenant_id' => $user->tenant_id,
        'name' => 'Warden Other '.uniqid(),
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
        ->and($policy->update($user, $other))->toBeFalse();

    $this->actingAs($user);
    $scope = UserAccessScope::for($user);

    expect($scope->level())->toBe(ScopeLevelEnum::AssignedHostels)
        ->and($scope->hostelIds())->toContain((int) $assigned->id)
        ->and($scope->hostelIds())->not->toContain((int) $other->id);
});

// --- Phase 10: Coursework capture ---

test('phase10 coursework capture disabled blocks mark mutations', function () {
    $context = createCourseWorkJsonApiContext();
    $classConfig = $context['classConfig']->load('departmentCourse');
    $departmentCourse = $classConfig->departmentCourse;
    expect($departmentCourse)->not->toBeNull();
    $departmentCourse->update(['coursework_capture_enabled' => false]);

    $module = $context['module'];
    $module->update(['capture_mark_only' => false]);

    $service = app(CourseWorkAssessmentLockService::class);

    expect(fn () => $service->assertMutationAllowed(
        $classConfig->fresh()->load('departmentCourse'),
        $module->fresh(),
        $context['assessmentType']->id,
    ))->toThrow(ValidationException::class);
});

test('phase10 capture mark only modules bypass coursework capture disable', function () {
    $context = createCourseWorkJsonApiContext();
    $classConfig = $context['classConfig']->load('departmentCourse');
    $classConfig->departmentCourse->update(['coursework_capture_enabled' => false]);
    $module = $context['module'];
    $module->update(['capture_mark_only' => true]);

    $service = app(CourseWorkAssessmentLockService::class);

    $service->assertMutationAllowed(
        $classConfig->fresh()->load('departmentCourse'),
        $module->fresh(),
        $context['assessmentType']->id,
    );

    expect(true)->toBeTrue();
});

test('phase10 vp academics pack includes toggle coursework capture and edit ui exists', function () {
    expect(PermissionHelper::vpAcademicsPermissions())->toContain('toggle:coursework-capture');

    $edit = file_get_contents(resource_path('js/pages/institution/departments/courses/Edit.vue'));
    expect($edit)->toContain('coursework_capture_enabled');
});

// --- Academic top/bottom courses UI ---

test('phase4 academic tab includes top and bottom performing courses widgets', function () {
    $academicTab = file_get_contents(resource_path('js/pages/dashboard/tabs/AcademicTab.vue'));
    $sidebar = file_get_contents(resource_path('js/composables/core/useSidebarMenu.ts'));

    expect($academicTab)->toContain('topPerformingCourses')
        ->and($academicTab)->toContain('bottomPerformingCourses')
        ->and($sidebar)->toContain('my_departments');
});
