<?php

use App\Enums\Enrolments\BulkFinaliseEnrolmentAuditEventEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Jobs\Enrolments\BulkFinaliseEnrolmentsJob;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Enrolments\BulkFinaliseEnrolmentAuditLog;
use App\Models\Enrolments\ClassList;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Users\User;
use App\Services\Enrolments\BulkFinaliseEnrolmentsService;
use App\Services\Enrolments\StudentBankPaymentMatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

require_once __DIR__.'/../../Support/BulkFinaliseTestHelpers.php';
require_once __DIR__.'/MaintenanceControllerTest.php';

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-01-15 12:00:00', config('app.timezone')));

    AcademicCalendar::query()->firstOrCreate(
        [
            'calendar_year' => '2025/2026',
            'type' => 'semester',
        ],
        [
            'opening_date' => '2026-01-01',
            'closing_date' => '2026-12-31',
        ],
    );

    foreach (['Semester 1', 'Semester 2'] as $name) {
        AcademicYearOption::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'description' => null],
        );
    }

    foreach (['Active', 'Completed'] as $name) {
        StudentEnrolmentStatus::query()->firstOrCreate(
            ['name' => $name],
            ['description' => 'Test'],
        );
    }
});

afterEach(function (): void {
    Carbon::setTestNow(null);
});

it('redirects guests from verified students final enrolment page', function (): void {
    $this->get(route('maintenance.verified-students-final-enrolment'))
        ->assertRedirect('/login');
});

it('forbids users without root manage from verified students final enrolment page', function (): void {
    $user = User::factory()->create();
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

it('filters verified students by payment status', function (): void {
    actingAsRootMaintenanceUser();
    config()->set('custom.bank-statements.plan_anchor_start', '2026-01-01');

    $paidApplication = createVerifiedStudentApplication('STU-VFE-PAY-PAID');
    createBankCreditReceipt('STU-VFE-PAY-PAID', '2026-01-10 09:00:00', 'TXN-VFE-PAY-PAID');

    $unpaidApplication = createVerifiedStudentApplication('STU-VFE-PAY-UNPAID');

    $missingNumberApplication = createVerifiedStudentApplication('STU-VFE-PAY-MISSING');
    Student::query()->whereKey($missingNumberApplication->student_id)->update(['student_number' => '']);

    $eligibleIds = collect($this->getJson(route('maintenance.verified-students-final-enrolment.data', [
        'payment_status' => 'eligible',
    ]))->json('data'))->pluck('id');

    expect($eligibleIds)->toContain($paidApplication->id)
        ->and($eligibleIds)->not->toContain($unpaidApplication->id, $missingNumberApplication->id);

    $noPaymentIds = collect($this->getJson(route('maintenance.verified-students-final-enrolment.data', [
        'payment_status' => 'no_payment',
    ]))->json('data'))->pluck('id');

    expect($noPaymentIds)->toContain($unpaidApplication->id)
        ->and($noPaymentIds)->not->toContain($paidApplication->id, $missingNumberApplication->id);

    $missingNumberIds = collect($this->getJson(route('maintenance.verified-students-final-enrolment.data', [
        'payment_status' => 'missing_student_number',
    ]))->json('data'))->pluck('id');

    expect($missingNumberIds)->toContain($missingNumberApplication->id)
        ->and($missingNumberIds)->not->toContain($paidApplication->id, $unpaidApplication->id);

    $summary = $this->getJson(route('maintenance.verified-students-final-enrolment.summary'))
        ->assertSuccessful()
        ->json('summary');

    expect($summary['eligible'] + $summary['noPayment'] + $summary['missingStudentNumber'])
        ->toBe($summary['total'])
        ->and($summary['missingStudentNumber'])->toBeGreaterThanOrEqual(1)
        ->and($summary['eligible'])->toBeGreaterThanOrEqual(1)
        ->and($summary['noPayment'])->toBeGreaterThanOrEqual(1);
});

it('force finalises selected students without matching payment', function (): void {
    actingAsRootMaintenanceUser();
    config()->set('custom.bank-statements.plan_anchor_start', '2026-01-01');

    $unpaidApplication = createVerifiedStudentApplication('STU-VFE-FORCE-001');
    createEnrolledDepartmentStep($unpaidApplication);

    $runId = (string) Str::uuid();
    $service = app(BulkFinaliseEnrolmentsService::class);
    ['start_date' => $startDate, 'end_date' => $endDate] = app(StudentBankPaymentMatcher::class)->resolveDefaultDateRange();

    $service->run(
        startDate: $startDate,
        endDate: $endDate,
        dryRun: false,
        runId: $runId,
        initiatedByUserId: auth()->id(),
        studentApplicationIds: [$unpaidApplication->id],
        forceFinalise: true,
    );

    $classList = ClassList::query()->where('student_application_id', $unpaidApplication->id)->first();

    expect($classList?->type)->toBe(ClassListTypeEnum::FINAL);
});

it('rejects bulk finalise for invalid student application ids', function (): void {
    actingAsRootMaintenanceUser();

    $this->postJson(route('maintenance.verified-students-final-enrolment.run'), [
        'student_application_ids' => [999999999],
        'force_finalise' => true,
    ])->assertUnprocessable();
});

it('persists audit logs when bulk finalise is dispatched and run', function (): void {
    $user = actingAsRootMaintenanceUser();
    config()->set('custom.bank-statements.plan_anchor_start', '2026-01-01');

    $paidApplication = createVerifiedStudentApplication('STU-VFE-AUDIT-001');
    createEnrolledDepartmentStep($paidApplication);
    createBankCreditReceipt('STU-VFE-AUDIT-001', '2026-01-10 09:00:00', 'TXN-VFE-AUDIT-001');

    $response = $this->postJson(route('maintenance.verified-students-final-enrolment.run'))
        ->assertSuccessful();

    $runId = $response->json('runId');

    expect(BulkFinaliseEnrolmentAuditLog::query()->where('run_id', $runId)->count())->toBeGreaterThanOrEqual(1)
        ->and(BulkFinaliseEnrolmentAuditLog::query()
            ->where('run_id', $runId)
            ->where('event', BulkFinaliseEnrolmentAuditEventEnum::RunStarted)
            ->where('user_id', $user->id)
            ->exists())->toBeTrue();

    $job = new BulkFinaliseEnrolmentsJob(
        runId: $runId,
        startDate: $response->json('startDate'),
        endDate: $response->json('endDate'),
        initiatedByUserId: $user->id,
    );

    $job->handle(app(BulkFinaliseEnrolmentsService::class));

    expect(BulkFinaliseEnrolmentAuditLog::query()
        ->where('run_id', $runId)
        ->where('event', BulkFinaliseEnrolmentAuditEventEnum::RunCompleted)
        ->exists())->toBeTrue()
        ->and(BulkFinaliseEnrolmentAuditLog::query()
            ->where('run_id', $runId)
            ->where('event', BulkFinaliseEnrolmentAuditEventEnum::StudentFinalised)
            ->where('student_application_id', $paidApplication->id)
            ->exists())->toBeTrue();
});
