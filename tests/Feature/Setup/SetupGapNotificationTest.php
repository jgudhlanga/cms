<?php

use App\Models\Setup\SetupGap;
use App\Notifications\Setup\SetupGapsDetectedNotification;
use App\Services\Setup\SetupGapNotifier;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    seedDashboardTestRoles();
    Notification::fake();
});

it('tells the head of department, vice principal academics and super users about new gaps', function () {
    $context = makeSetupGapClassContext();

    $head = makeSetupGapStaffUser($context['tenantId'], 'head-of-department', $context['institutionDepartment']);
    $vicePrincipal = makeSetupGapStaffUser($context['tenantId'], 'vice-principal-academics');
    $superUser = makeSetupGapStaffUser($context['tenantId'], 'super-user');

    scanSetupGaps();
    app(SetupGapNotifier::class)->notifyPending();

    Notification::assertSentTo($head, SetupGapsDetectedNotification::class);
    Notification::assertSentTo($vicePrincipal, SetupGapsDetectedNotification::class);
    Notification::assertSentTo($superUser, SetupGapsDetectedNotification::class);
});

it('does not notify someone who cannot act on the gap', function () {
    $context = makeSetupGapClassContext();

    // A lecturer sits in the department but holds none of the abilities these fixes require.
    $lecturer = makeSetupGapStaffUser($context['tenantId'], 'lecturer', $context['institutionDepartment']);
    $head = makeSetupGapStaffUser($context['tenantId'], 'head-of-department', $context['institutionDepartment']);

    scanSetupGaps();
    app(SetupGapNotifier::class)->notifyPending();

    Notification::assertSentTo($head, SetupGapsDetectedNotification::class);
    Notification::assertNotSentTo($lecturer, SetupGapsDetectedNotification::class);
});

it('tells a head of department about their own department only', function () {
    $own = makeSetupGapClassContext();
    $other = makeSetupGapClassContext();

    $head = makeSetupGapStaffUser($own['tenantId'], 'head-of-department', $own['institutionDepartment']);

    scanSetupGaps();
    app(SetupGapNotifier::class)->notifyPending();

    Notification::assertSentTo($head, function (SetupGapsDetectedNotification $notification) use ($own, $other): bool {
        $departmentIds = $notification->gaps
            ->map(fn (SetupGap $gap): int => (int) $gap->institution_department_id)
            ->unique()
            ->all();

        return $departmentIds === [(int) $own['institutionDepartment']->id]
            && ! in_array((int) $other['institutionDepartment']->id, $departmentIds, true);
    });
});

it('sends setup alerts in app only, never by email', function () {
    $context = makeSetupGapClassContext();
    $head = makeSetupGapStaffUser($context['tenantId'], 'head-of-department', $context['institutionDepartment']);

    scanSetupGaps();
    app(SetupGapNotifier::class)->notifyPending();

    Notification::assertSentTo($head, function (SetupGapsDetectedNotification $notification, array $channels) use ($head): bool {
        return $channels === ['database'] && $notification->via($head) === ['database'];
    });
});

it('announces each gap once', function () {
    $context = makeSetupGapClassContext();
    makeSetupGapStaffUser($context['tenantId'], 'super-user');

    scanSetupGaps();
    $firstRun = app(SetupGapNotifier::class)->notifyPending();
    $secondRun = app(SetupGapNotifier::class)->notifyPending();

    expect($firstRun)->toBeGreaterThan(0)
        ->and($secondRun)->toBe(0)
        ->and(SetupGap::query()->open()->whereNull('notified_at')->count())->toBe(0);
});

it('announces a gap again when it comes back after being fixed', function () {
    $context = makeSetupGapClassContext();
    makeSetupGapStaffUser($context['tenantId'], 'super-user');

    scanSetupGaps();
    app(SetupGapNotifier::class)->notifyPending();

    SetupGap::query()->update(['resolved_at' => now()]);
    scanSetupGaps();

    expect(app(SetupGapNotifier::class)->notifyPending())->toBeGreaterThan(0);
});

it('tells a head of division about every department under them', function () {
    $first = makeSetupGapClassContext();
    $second = makeSetupGapClassContext();
    $outside = makeSetupGapClassContext();

    $head = makeSetupGapDivisionHead($first['tenantId'], [
        $first['institutionDepartment'],
        $second['institutionDepartment'],
    ]);

    scanSetupGaps();
    app(SetupGapNotifier::class)->notifyPending();

    Notification::assertSentTo($head, function (SetupGapsDetectedNotification $notification) use ($first, $second, $outside): bool {
        $departmentIds = $notification->gaps
            ->map(fn (SetupGap $gap): int => (int) $gap->institution_department_id)
            ->unique()
            ->all();

        return in_array((int) $first['institutionDepartment']->id, $departmentIds, true)
            && in_array((int) $second['institutionDepartment']->id, $departmentIds, true)
            && ! in_array((int) $outside['institutionDepartment']->id, $departmentIds, true);
    });
});
