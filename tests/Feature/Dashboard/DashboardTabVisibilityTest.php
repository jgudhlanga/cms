<?php

use App\Enums\Rbac\RoleEnum;
use App\Helpers\PermissionHelper;
use App\Models\Rbac\Role;
use App\Models\Users\User;
use App\Services\Dashboard\DashboardModuleService;

beforeEach(function () {
    enableDashboardModule();
    seedDashboardAcademicCalendar();
});

test('dashboard returns forbidden when module is disabled', function () {
    $user = userWithDashboardPermission();
    dashboardsModule()->update(['status' => false]);
    app(DashboardModuleService::class)->clearCache();

    $this->actingAs($user)
        ->get(dashboardUrlFor($user))
        ->assertForbidden();
});

test('dashboard returns forbidden when user has no tab permissions', function () {
    $user = User::factory()->create();
    seedDashboardIntakePeriod($user->tenant_id);

    $this->actingAs($user)
        ->get(dashboardUrlFor($user))
        ->assertForbidden();
});

test('dashboard is accessible with academic tab permission only', function () {
    $user = userWithDashboardPermission('view-academic:dashboards');

    enableDashboardModule([
        'overview' => false,
        'academic' => true,
        'examinations' => false,
    ]);

    $this->actingAs($user)
        ->get(dashboardUrlFor($user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('visibleTabs', ['academic'])
        );
});

test('dashboard shows examinations tab with view-examinations permission', function () {
    $user = userWithDashboardPermission('view-examinations:dashboards');

    enableDashboardModule([
        'overview' => false,
        'academic' => false,
        'enrolments' => false,
        'attendance' => false,
        'staff' => false,
        'finance' => false,
        'hostel' => false,
        'examinations' => true,
    ]);

    $this->actingAs($user)
        ->get(dashboardUrlFor($user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('visibleTabs', ['examinations'])
            ->has('filters')
            ->has('filterOptions')
            ->has('statusCounts')
        );
});

test('dashboard examinations filters are accepted on the main dashboard', function () {
    $user = userWithDashboardPermission('view-examinations:dashboards');

    enableDashboardModule([
        'overview' => false,
        'academic' => false,
        'enrolments' => false,
        'attendance' => false,
        'staff' => false,
        'finance' => false,
        'hostel' => false,
        'examinations' => true,
    ]);

    $this->actingAs($user)
        ->get(dashboardUrlFor($user).'&session=45000')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('filters.session', '45000')
        );
});

test('dashboard hides tabs disabled in module settings', function () {
    $user = userWithDashboardPermission();

    enableDashboardModule([
        'academic' => false,
        'enrolments' => false,
        'attendance' => false,
        'staff' => false,
        'finance' => false,
        'hostel' => false,
        'examinations' => false,
    ]);

    $this->actingAs($user)
        ->get(dashboardUrlFor($user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('visibleTabs', ['overview'])
        );
});

test('dashboard title reflects highest priority user role', function () {
    seedDashboardTestRoles();

    $user = userWithDashboardPermission();
    $principalRole = Role::query()->where('name', RoleEnum::PRINCIPAL->name())->firstOrFail();
    $user->assignRole($principalRole);

    $this->actingAs($user->fresh())
        ->get(dashboardUrlFor($user))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('dashboardTitle', "Harare Polytechnic — Principal's Dashboard")
        );
});

test('leadership packs include examinations dashboard permission and hod does not', function () {
    expect(PermissionHelper::vpAcademicsPermissions())->toContain('view-examinations:dashboards')
        ->and(PermissionHelper::vpAdminPermissions())->toContain('view-examinations:dashboards')
        ->and(PermissionHelper::principalPermissions())->toContain('view-examinations:dashboards')
        ->and(PermissionHelper::hodPermissions())->not->toContain('view-examinations:dashboards');

    expect(config('permissions.groups.dashboards'))->toContain('view-examinations:dashboards');
});
