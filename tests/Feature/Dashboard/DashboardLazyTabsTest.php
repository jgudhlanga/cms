<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Http\Request;

/*
 * Only the active dashboard tab is computed with the page. Other tabs are optional props that the page
 * fetches with a partial reload the first time they are opened.
 */

beforeEach(function () {
    enableDashboardModule();
    seedDashboardAcademicCalendar();
});

/**
 * @return array<string, string>
 */
function dashboardPartialReloadHeaders(string $props): array
{
    return [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(Request::create('/')),
        'X-Inertia-Partial-Component' => 'dashboard/Index',
        'X-Inertia-Partial-Data' => $props,
    ];
}

test('the page carries only the active tab data', function () {
    $user = userWithFullOverviewDashboardPermission();

    $this->actingAs($user)
        ->get(dashboardUrlFor($user))
        ->assertOk()
        ->assertInertia(function ($page) {
            $visibleTabs = $page->toArray()['props']['visibleTabs'];

            expect($visibleTabs)->toContain('overview', 'staff');

            return $page
                ->where('activeTab', $visibleTabs[0])
                ->has('overviewDashboard')
                ->missing('staffDashboard')
                ->missing('hostelDashboard')
                ->missing('enrolmentSummary');
        });
});

test('the tab query parameter chooses which tab loads with the page', function () {
    $user = userWithFullOverviewDashboardPermission();

    $this->actingAs($user)
        ->get(dashboardUrlFor($user).'&tab=staff')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('activeTab', 'staff')
            ->has('staffDashboard')
            ->missing('overviewDashboard'));
});

test('a partial reload loads another tab on demand', function () {
    $user = userWithFullOverviewDashboardPermission();

    $this->actingAs($user)
        ->withHeaders(dashboardPartialReloadHeaders('staffDashboard'))
        ->get(dashboardUrlFor($user).'&tab=staff')
        ->assertOk()
        ->assertJsonPath('component', 'dashboard/Index')
        ->assertJsonStructure(['props' => ['staffDashboard']])
        ->assertJsonMissingPath('props.overviewDashboard');
});

test('a tab the user cannot see is never loaded', function () {
    $user = userWithOverviewDashboardPermission();

    $this->actingAs($user)
        ->get(dashboardUrlFor($user).'&tab=finance')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('visibleTabs', ['overview'])
            ->where('activeTab', 'overview'));

    $this->actingAs($user)
        ->withHeaders(dashboardPartialReloadHeaders('financeDashboard'))
        ->get(dashboardUrlFor($user).'&tab=finance')
        ->assertOk()
        ->assertJsonPath('props.financeDashboard', null);
});
