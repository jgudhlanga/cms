<?php

use App\Jobs\Enrolments\BulkFinaliseEnrolmentsJob;
use App\Services\Enrolments\BulkFinaliseEnrolmentsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

require_once __DIR__.'/../../Support/BulkFinaliseTestHelpers.php';
require_once __DIR__.'/MaintenanceControllerTest.php';

it('redirects guests from verified students final enrolment page', function (): void {
    $this->get(route('maintenance.verified-students-final-enrolment'))
        ->assertRedirect('/login');
});

it('forbids users without root manage from verified students final enrolment page', function (): void {
    $user = \App\Models\Users\User::factory()->create();
    $this->actingAs($user)
        ->get(route('maintenance.verified-students-final-enrolment'))
        ->assertForbidden();
});

it('renders verified students final enrolment page for root users', function (): void {
    actingAsRootMaintenanceUser();

    $this->get(route('maintenance.verified-students-final-enrolment'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('maintenance/VerifiedStudentsFinalEnrolment')
            ->has('paymentWindow.startDate')
            ->has('paymentWindow.endDate'));
});

it('returns verified students with payment eligibility flags', function (): void {
    actingAsRootMaintenanceUser();
    config()->set('custom.bank-statements.plan_anchor_start', '2026-01-01');

    $paidApplication = createVerifiedStudentApplication('STU-VFE-001');
    createBankCreditReceipt('STU-VFE-001', '2026-01-10 09:00:00', 'TXN-VFE-001');

    $unpaidApplication = createVerifiedStudentApplication('STU-VFE-002');

    $response = $this->getJson(route('maintenance.verified-students-final-enrolment.data'))
        ->assertSuccessful()
        ->assertJsonStructure([
            'data',
            'summary' => ['total', 'eligible', 'noPayment', 'missingStudentNumber', 'paymentSummaryReady'],
            'paymentWindow' => ['startDate', 'endDate'],
        ]);

    $data = collect($response->json('data'));

    expect($data->pluck('id'))->toContain($paidApplication->id, $unpaidApplication->id);

    $paidRow = $data->firstWhere('id', $paidApplication->id);
    $unpaidRow = $data->firstWhere('id', $unpaidApplication->id);

    expect($paidRow['attributes']['paymentEligibility'])->toBe('eligible')
        ->and($paidRow['attributes']['hasMatchingPayment'])->toBeTrue()
        ->and($unpaidRow['attributes']['paymentEligibility'])->toBe('no_payment')
        ->and($unpaidRow['attributes']['hasMatchingPayment'])->toBeFalse()
        ->and($response->json('summary.total'))->toBeGreaterThanOrEqual(2)
        ->and($response->json('summary.paymentSummaryReady'))->toBeFalse();

    $summaryResponse = $this->getJson(route('maintenance.verified-students-final-enrolment.summary'))
        ->assertSuccessful();

    expect($summaryResponse->json('summary.eligible'))->toBeGreaterThanOrEqual(1)
        ->and($summaryResponse->json('summary.noPayment'))->toBeGreaterThanOrEqual(1)
        ->and($summaryResponse->json('summary.paymentSummaryReady'))->toBeTrue();
});

it('filters verified students by department, level, and course', function (): void {
    actingAsRootMaintenanceUser();
    config()->set('custom.bank-statements.plan_anchor_start', '2026-01-01');

    $matchedApplication = createVerifiedStudentApplication('STU-VFE-FILTER-MATCH');
    $otherApplication = createVerifiedStudentApplication('STU-VFE-FILTER-OTHER');

    $departmentId = (int) $matchedApplication->institution_department_id;
    $levelId = (int) $matchedApplication->departmentLevel->level_id;
    $courseId = (int) $matchedApplication->department_course_id;

    $matchedResponse = $this->getJson(route('maintenance.verified-students-final-enrolment.data', [
        'department' => [$departmentId],
        'level' => [$levelId],
        'course' => [$courseId],
    ]))->assertSuccessful();

    $matchedIds = collect($matchedResponse->json('data'))->pluck('id');

    expect($matchedIds)->toContain($matchedApplication->id)
        ->and($matchedIds)->not->toContain($otherApplication->id);

    $summaryResponse = $this->getJson(route('maintenance.verified-students-final-enrolment.summary', [
        'department' => [$departmentId],
        'level' => [$levelId],
        'course' => [$courseId],
    ]))->assertSuccessful();

    expect($summaryResponse->json('summary.total'))->toBeGreaterThanOrEqual(1)
        ->and($summaryResponse->json('summary.paymentSummaryReady'))->toBeTrue();
});

it('dispatches bulk finalise job for root users', function (): void {
    actingAsRootMaintenanceUser();
    Queue::fake();
    config()->set('custom.bank-statements.plan_anchor_start', '2026-01-01');

    createVerifiedStudentApplication('STU-VFE-003');

    $response = $this->postJson(route('maintenance.verified-students-final-enrolment.run'))
        ->assertSuccessful()
        ->assertJsonStructure(['runId', 'startDate', 'endDate', 'message']);

    Queue::assertPushed(BulkFinaliseEnrolmentsJob::class, function (BulkFinaliseEnrolmentsJob $job) use ($response): bool {
        return $job->runId === $response->json('runId')
            && $job->timeout >= 3600
            && $job->tries === 1;
    });
});

it('rejects concurrent bulk finalise runs', function (): void {
    actingAsRootMaintenanceUser();
    Cache::put(BulkFinaliseEnrolmentsService::ACTIVE_RUN_LOCK_KEY, 'existing-run', 3600);

    $this->postJson(route('maintenance.verified-students-final-enrolment.run'))
        ->assertStatus(409)
        ->assertJsonFragment([
            'message' => __('trans.maintenance_verified_students_final_enrolment_run_already_active'),
        ]);
});

it('returns bulk finalise run status from cache', function (): void {
    actingAsRootMaintenanceUser();
    $runId = 'test-run-id';

    app(BulkFinaliseEnrolmentsService::class)->writeRunProgress($runId, [
        'status' => 'running',
        'processed' => 2,
        'total' => 5,
        'successful' => 1,
        'failed' => 1,
        'message' => null,
    ]);

    $this->getJson(route('maintenance.verified-students-final-enrolment.run-status', $runId))
        ->assertSuccessful()
        ->assertJson([
            'status' => 'running',
            'processed' => 2,
            'total' => 5,
            'successful' => 1,
            'failed' => 1,
        ]);
});

it('returns 404 for unknown bulk finalise run status', function (): void {
    actingAsRootMaintenanceUser();

    $this->getJson(route('maintenance.verified-students-final-enrolment.run-status', 'missing-run'))
        ->assertNotFound();
});
