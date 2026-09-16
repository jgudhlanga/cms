<?php

use App\Models\Setup\SetupGap;
use App\Models\Users\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    seedDashboardTestRoles();
});

/**
 * The class-level gaps are fixed on the department classes screen, so seeing them needs that ability.
 */
function grantClassSetupAbility(User $user): User
{
    Permission::findOrCreate('update:academic-calendars', 'web');
    $user->givePermissionTo('update:academic-calendars');

    return $user->fresh();
}

it('shows a department user only their own department gaps', function () {
    $own = makeSetupGapClassContext();
    $other = makeSetupGapClassContext();

    scanSetupGaps();

    grantClassSetupAbility(actingAsDepartmentScopedUser($own['tenantId'], $own['institutionDepartment']));

    $response = $this->getJson(route('setup-gaps.index'));

    $response->assertOk();

    $departmentIds = SetupGap::query()
        ->whereIn('id', collect($response->json('gaps'))->pluck('id'))
        ->pluck('institution_department_id')
        ->unique()
        ->all();

    expect($departmentIds)->toBe([$own['institutionDepartment']->id])
        ->and($response->json('openCount'))->toBe(count($response->json('gaps')));

    expect(collect($response->json('gaps'))->pluck('id'))
        ->not->toContain((string) SetupGap::query()
        ->where('institution_department_id', $other['institutionDepartment']->id)
        ->value('id'));
});

it('hides gaps from a user who cannot act on them', function () {
    $context = makeSetupGapClassContext();
    scanSetupGaps();

    // In the department, but without the ability the fix screen requires.
    actingAsDepartmentScopedUser($context['tenantId'], $context['institutionDepartment']);

    $response = $this->getJson(route('setup-gaps.index'));

    $response->assertOk();

    expect($response->json('gaps'))->toBe([])
        ->and($response->json('openCount'))->toBe(0);
});

it('returns just the count when asked for it', function () {
    $context = makeSetupGapClassContext();
    scanSetupGaps();

    grantClassSetupAbility(actingAsDepartmentScopedUser($context['tenantId'], $context['institutionDepartment']));

    $response = $this->getJson(route('setup-gaps.index', ['count_only' => 1]));

    $response->assertOk()
        ->assertJsonMissingPath('gaps');

    expect($response->json('openCount'))->toBeGreaterThan(0);
});

it('requires a signed in user', function () {
    $this->getJson(route('setup-gaps.index'))->assertUnauthorized();
});

it('shows a head of department their own department only', function () {
    $own = makeSetupGapClassContext();
    $other = makeSetupGapClassContext();

    $head = makeSetupGapStaffUser($own['tenantId'], 'head-of-department', $own['institutionDepartment']);

    scanSetupGaps();

    $this->actingAs($head);
    $response = $this->getJson(route('setup-gaps.index'));
    $response->assertOk();

    $departmentIds = SetupGap::query()
        ->whereIn('id', collect($response->json('gaps'))->pluck('id'))
        ->pluck('institution_department_id')
        ->unique()
        ->values()
        ->all();

    expect($departmentIds)->toBe([$own['institutionDepartment']->id])
        ->and($departmentIds)->not->toContain($other['institutionDepartment']->id);
});

it('shows a head of division every department under them', function () {
    $first = makeSetupGapClassContext();
    $second = makeSetupGapClassContext();
    $outside = makeSetupGapClassContext();

    $head = makeSetupGapDivisionHead($first['tenantId'], [
        $first['institutionDepartment'],
        $second['institutionDepartment'],
    ]);

    scanSetupGaps();

    $this->actingAs($head);
    $response = $this->getJson(route('setup-gaps.index'));
    $response->assertOk();

    $departmentIds = SetupGap::query()
        ->whereIn('id', collect($response->json('gaps'))->pluck('id'))
        ->pluck('institution_department_id')
        ->unique()
        ->sort()
        ->values()
        ->all();

    $expected = collect([$first['institutionDepartment']->id, $second['institutionDepartment']->id])
        ->sort()
        ->values()
        ->all();

    expect($departmentIds)->toBe($expected)
        ->and($departmentIds)->not->toContain($outside['institutionDepartment']->id);
});
