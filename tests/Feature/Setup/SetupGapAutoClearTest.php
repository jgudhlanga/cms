<?php

use App\Enums\Setup\SetupGapCheckEnum;
use App\Jobs\Setup\RecheckSetupGapsJob;
use App\Models\AcademicCalendars\ClassConfigLecturerInCharge;
use App\Models\Setup\SetupGap;
use App\Notifications\Setup\SetupGapsDetectedNotification;
use App\Services\Setup\SetupGapNotifier;
use App\Services\Setup\SetupGapScanner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    seedDashboardTestRoles();
});

function licGapFor(int $institutionDepartmentId): ?SetupGap
{
    return SetupGap::query()
        ->where('check_key', SetupGapCheckEnum::CLASS_CONFIG_WITHOUT_LECTURER_IN_CHARGE->value)
        ->where('institution_department_id', $institutionDepartmentId)
        ->first();
}

it('queues a re-check of the affected alerts when the configuration is saved', function () {
    Queue::fake();

    $context = makeSetupGapClassContext();
    $head = makeSetupGapStaffUser($context['tenantId'], 'head-of-department', $context['institutionDepartment']);

    // Exactly what assigning a lecturer in charge on the fix screen does.
    ClassConfigLecturerInCharge::query()->create([
        'tenant_id' => $context['tenantId'],
        'class_config_id' => $context['classConfigId'],
        'staff_id' => DB::table('staff')->where('user_id', $head->id)->value('id'),
    ]);

    Queue::assertPushed(RecheckSetupGapsJob::class, function (RecheckSetupGapsJob $job): bool {
        return in_array(
            SetupGapCheckEnum::CLASS_CONFIG_WITHOUT_LECTURER_IN_CHARGE->value,
            $job->checkKeys,
            true,
        );
    });
});

it('clears the alert when that re-check runs, with nobody running a command', function () {
    $context = makeSetupGapClassContext();
    $head = makeSetupGapStaffUser($context['tenantId'], 'head-of-department', $context['institutionDepartment']);

    scanSetupGaps();
    expect(licGapFor($context['institutionDepartment']->id)?->resolved_at)->toBeNull();

    ClassConfigLecturerInCharge::query()->create([
        'tenant_id' => $context['tenantId'],
        'class_config_id' => $context['classConfigId'],
        'staff_id' => DB::table('staff')->where('user_id', $head->id)->value('id'),
    ]);

    // The queued re-check, as the worker would run it.
    (new RecheckSetupGapsJob([SetupGapCheckEnum::CLASS_CONFIG_WITHOUT_LECTURER_IN_CHARGE->value]))
        ->handle(app(SetupGapScanner::class), app(SetupGapNotifier::class));

    expect(licGapFor($context['institutionDepartment']->id)->resolved_at)->not->toBeNull();
});

it('re-checks only the alerts the saved configuration affects', function () {
    $context = makeSetupGapClassContext();

    scanSetupGaps();

    $syllabusGap = SetupGap::query()
        ->where('check_key', SetupGapCheckEnum::CLASS_CONFIG_WITHOUT_SYLLABUS->value)
        ->where('institution_department_id', $context['institutionDepartment']->id)
        ->first();

    expect($syllabusGap->resolved_at)->toBeNull();

    // Fix the syllabus in the database only, then re-check a different check.
    DB::table('class_configs')
        ->where('id', $context['classConfigId'])
        ->update(['course_syllabus_ids' => json_encode([1, 2])]);

    (new RecheckSetupGapsJob([SetupGapCheckEnum::CLASS_CONFIG_WITHOUT_LECTURER_IN_CHARGE->value]))
        ->handle(app(SetupGapScanner::class), app(SetupGapNotifier::class));

    // Untouched: a targeted re-check must not resolve alerts it did not look at.
    expect($syllabusGap->fresh()->resolved_at)->toBeNull();

    (new RecheckSetupGapsJob([SetupGapCheckEnum::CLASS_CONFIG_WITHOUT_SYLLABUS->value]))
        ->handle(app(SetupGapScanner::class), app(SetupGapNotifier::class));

    expect($syllabusGap->fresh()->resolved_at)->not->toBeNull();
});

it('drops a fixed alert from the list when the user presses refresh', function () {
    $context = makeSetupGapClassContext();
    $head = makeSetupGapStaffUser($context['tenantId'], 'head-of-department', $context['institutionDepartment']);

    scanSetupGaps();
    expect(licGapFor($context['institutionDepartment']->id)?->resolved_at)->toBeNull();

    // Fixed straight in the database, so no observer fires — exactly the case refresh exists for.
    ClassConfigLecturerInCharge::query()->insert([
        'tenant_id' => $context['tenantId'],
        'class_config_id' => $context['classConfigId'],
        'staff_id' => DB::table('staff')->where('user_id', $head->id)->value('id'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($head);
    $response = $this->postJson(route('setup-gaps.refresh'));

    $response->assertOk();

    $checks = collect($response->json('gaps'))->pluck('check')->all();

    expect($checks)->not->toContain(SetupGapCheckEnum::CLASS_CONFIG_WITHOUT_LECTURER_IN_CHARGE->value)
        ->and(licGapFor($context['institutionDepartment']->id)->resolved_at)->not->toBeNull()
        ->and($response->json('openCount'))->toBe(count($response->json('gaps')));
});

it('re-checks only the named check when refreshing one row', function () {
    $context = makeSetupGapClassContext();
    $head = makeSetupGapStaffUser($context['tenantId'], 'head-of-department', $context['institutionDepartment']);

    scanSetupGaps();

    // Fix both problems in the database, then refresh only one of them.
    ClassConfigLecturerInCharge::query()->insert([
        'tenant_id' => $context['tenantId'],
        'class_config_id' => $context['classConfigId'],
        'staff_id' => DB::table('staff')->where('user_id', $head->id)->value('id'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('class_configs')->where('id', $context['classConfigId'])->update([
        'course_syllabus_ids' => json_encode([1, 2]),
    ]);

    $this->actingAs($head);
    $this->postJson(route('setup-gaps.refresh'), [
        'check' => SetupGapCheckEnum::CLASS_CONFIG_WITHOUT_LECTURER_IN_CHARGE->value,
    ])->assertOk();

    expect(licGapFor($context['institutionDepartment']->id)->resolved_at)->not->toBeNull();

    $syllabusGap = SetupGap::query()
        ->where('check_key', SetupGapCheckEnum::CLASS_CONFIG_WITHOUT_SYLLABUS->value)
        ->where('institution_department_id', $context['institutionDepartment']->id)
        ->first();

    // Untouched: refreshing one row must not silently resolve the others.
    expect($syllabusGap->resolved_at)->toBeNull();
});

it('refuses to refresh a check the user cannot see', function () {
    $context = makeSetupGapClassContext();
    $head = makeSetupGapStaffUser($context['tenantId'], 'head-of-department', $context['institutionDepartment']);

    $this->actingAs($head);

    // Hostels are not a head of department's to fix.
    $this->postJson(route('setup-gaps.refresh'), [
        'check' => SetupGapCheckEnum::HOSTEL_BEDS_VACANT_WITH_WAITING_APPLICANTS->value,
    ])->assertStatus(422);
});

it('requires a signed in user to refresh', function () {
    $this->postJson(route('setup-gaps.refresh'))->assertUnauthorized();
});

it('does not announce the waiting backlog when one screen is fixed', function () {
    Notification::fake();

    $context = makeSetupGapClassContext();
    $head = makeSetupGapStaffUser($context['tenantId'], 'head-of-department', $context['institutionDepartment']);
    makeSetupGapStaffUser($context['tenantId'], 'super-user');

    // A backlog nobody has been told about yet, exactly like the first scan after a deploy.
    scanSetupGaps();
    $backlog = SetupGap::query()->open()->whereNull('notified_at')->count();
    expect($backlog)->toBeGreaterThan(0);

    ClassConfigLecturerInCharge::query()->create([
        'tenant_id' => $context['tenantId'],
        'class_config_id' => $context['classConfigId'],
        'staff_id' => DB::table('staff')->where('user_id', $head->id)->value('id'),
    ]);

    (new RecheckSetupGapsJob([SetupGapCheckEnum::CLASS_CONFIG_WITHOUT_LECTURER_IN_CHARGE->value]))
        ->handle(app(SetupGapScanner::class), app(SetupGapNotifier::class));

    // Fixing something announces nothing: the backlog still belongs to the nightly sweep.
    Notification::assertNothingSent();
    expect(SetupGap::query()->open()->whereNull('notified_at')->count())->toBeGreaterThan(0);
});

it('announces a problem the re-check itself turns up', function () {
    Notification::fake();

    $context = makeSetupGapClassContext();
    $superUser = makeSetupGapStaffUser($context['tenantId'], 'super-user');

    // Nothing scanned yet, so this run raises the class gaps for the first time.
    (new RecheckSetupGapsJob([SetupGapCheckEnum::CLASS_CONFIG_WITHOUT_LECTURER_IN_CHARGE->value]))
        ->handle(app(SetupGapScanner::class), app(SetupGapNotifier::class));

    Notification::assertSentTo($superUser, SetupGapsDetectedNotification::class);
});
