<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Rbac\Module;
use App\Models\Rbac\Permission;
use App\Models\Institution\IntakePeriod;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Services\Dashboard\DashboardModuleService;
use Database\Seeders\Rbac\RoleGroupSeeder;
use Database\Seeders\Rbac\RolesTableSeeder;

function seedDashboardTestRoles(): void
{
    (new RoleGroupSeeder)->run();
    (new RolesTableSeeder)->run();
}

function seedDashboardIntakePeriod(?int $tenantId = null): IntakePeriod
{
    $tenantId ??= Tenant::query()->first()?->id ?? Tenant::query()->create([
        'name' => 'Harare Poly',
        'is_active' => true,
    ])->id;

    $intakePeriod = IntakePeriod::withoutGlobalScopes()
        ->where('tenant_id', $tenantId)
        ->orderByDesc('end_date')
        ->first();

    if ($intakePeriod instanceof IntakePeriod) {
        return $intakePeriod;
    }

    return IntakePeriod::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'name' => 'Semester 1 2026',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
        'calendar_year' => '2025/2026',
        'is_active' => true,
    ]);
}

function seedDashboardAcademicCalendar(): AcademicCalendar
{
    $today = now()->toDateString();

    $existing = AcademicCalendar::query()
        ->semesters()
        ->whereDate('opening_date', '<=', $today)
        ->whereDate('closing_date', '>=', $today)
        ->first();

    if ($existing instanceof AcademicCalendar) {
        return $existing;
    }

    return AcademicCalendar::query()->create([
        'calendar_year' => (string) now()->year,
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => now()->startOfYear()->toDateString(),
        'closing_date' => now()->endOfYear()->toDateString(),
    ]);
}

function dashboardsModule(): Module
{
    return Module::query()->where('slug', 'dashboards')->firstOrFail();
}

function enableDashboardModule(array $tabSettings = []): void
{
    $defaults = [
        'overview' => true,
        'academic' => true,
        'enrolments' => true,
        'attendance' => true,
        'staff' => true,
        'finance' => true,
        'hostel' => true,
    ];

    dashboardsModule()->update([
        'status' => true,
        'settings' => [
            'tabs' => array_merge($defaults, $tabSettings),
        ],
    ]);

    app(DashboardModuleService::class)->clearCache();
}

function userWithDashboardPermission(string $permission = 'view:dashboards'): User
{
    $user = User::factory()->create();
    seedDashboardIntakePeriod($user->tenant_id);
    Permission::findOrCreate($permission, 'web');
    $user->givePermissionTo($permission);

    return $user;
}

function userWithHostelDashboardPermission(): User
{
    $user = userWithDashboardPermission('view-hostel:dashboards');

    enableDashboardModule([
        'overview' => false,
        'academic' => false,
        'enrolments' => false,
        'attendance' => false,
        'staff' => false,
        'finance' => false,
        'hostel' => true,
    ]);

    return $user;
}

function userWithStaffDashboardPermission(): User
{
    $user = userWithDashboardPermission('view-staff:dashboards');

    enableDashboardModule([
        'overview' => false,
        'academic' => false,
        'enrolments' => false,
        'attendance' => false,
        'staff' => true,
        'finance' => false,
        'hostel' => false,
    ]);

    return $user;
}

function userWithAcademicDashboardPermission(): User
{
    $user = userWithDashboardPermission('view-academic:dashboards');

    enableDashboardModule([
        'overview' => false,
        'academic' => true,
        'enrolments' => false,
        'attendance' => false,
        'staff' => false,
        'finance' => false,
        'hostel' => false,
    ]);

    return $user;
}

function userWithOverviewDashboardPermission(): User
{
    $user = userWithDashboardPermission('view:dashboards');

    enableDashboardModule([
        'overview' => true,
        'academic' => false,
        'enrolments' => false,
        'attendance' => false,
        'staff' => false,
        'finance' => false,
        'hostel' => false,
    ]);

    return $user;
}

function userWithFullOverviewDashboardPermission(): User
{
    $permissions = [
        'view:dashboards',
        'view-academic:dashboards',
        'view-enrolment:dashboards',
        'view-staff:dashboards',
        'view-hostel:dashboards',
    ];

    $user = User::factory()->create();
    seedDashboardIntakePeriod($user->tenant_id);

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    enableDashboardModule();

    return $user;
}

function dashboardUrlFor(User $user, ?int $academicCalendarId = null): string
{
    $intakePeriod = seedDashboardIntakePeriod($user->tenant_id);
    $url = '/dashboard?intake_period_id='.$intakePeriod->id;

    if ($academicCalendarId !== null) {
        $url .= '&academic_calendar_id='.$academicCalendarId;
    }

    return $url;
}
