<?php

use App\Models\Acl\Module;
use App\Models\Acl\Permission;
use App\Models\Institution\IntakePeriod;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Services\Dashboard\DashboardModuleService;
use Database\Seeders\Acl\RoleGroupSeeder;
use Database\Seeders\Acl\RolesTableSeeder;

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

function dashboardUrlFor(User $user): string
{
    $intakePeriod = seedDashboardIntakePeriod($user->tenant_id);

    return '/dashboard?intake_period_id='.$intakePeriod->id;
}
