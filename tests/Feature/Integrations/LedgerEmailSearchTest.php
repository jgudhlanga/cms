<?php

use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\HMS\HostelApplicationTypeEnum;
use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Enums\Shared\ModuleEnum;
use App\Enums\Students\ApplicationFeeStatusEnum;
use App\Enums\Students\IdCardRequestReasonEnum;
use App\Enums\Students\IdCardRequestStatusEnum;
use App\Models\HMS\HostelApplication;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Ledgers\Ledger;
use App\Models\Rbac\Module;
use App\Models\Rbac\Permission;
use App\Models\Shared\FeeType;
use App\Models\Students\ApplicationFee;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentIdCardRequest;
use App\Models\Users\User;
use App\Services\Rbac\RbacModuleStateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\Fluent\AssertableJson;

function ledgerSearchAuthUser(array $permissions = ['view:payments-debug']): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    return $user;
}

function ledgerSearchFeeType(FeeTypeEnum $feeTypeEnum): FeeType
{
    return FeeType::query()->firstOrCreate(
        ['slug' => $feeTypeEnum->slug()],
        [
            'name' => $feeTypeEnum->name(),
            'description' => $feeTypeEnum->description(),
            'position' => $feeTypeEnum->position(),
        ],
    );
}

function ledgerSearchIntakePeriod(): IntakePeriod
{
    return ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
}

function ledgerSearchCreateLedgerPair(
    object $ledgerable,
    FeeType $feeType,
    string $orderReference,
    int $intakePeriodId,
    int $tenantId,
): array {
    $shared = [
        'tenant_id' => $tenantId,
        'ledgerable_type' => $ledgerable::class,
        'ledgerable_id' => $ledgerable->id,
        'fee_type_id' => $feeType->id,
        'system_reference' => $orderReference,
        'intake_period_id' => $intakePeriodId,
        'payment_gateway' => 'smile-n-pay',
    ];

    $invoice = Ledger::query()->create(array_merge($shared, [
        'type' => 'invoice',
        'payment_status' => 'pending',
        'amount' => 150.00,
    ]));

    $receipt = Ledger::query()->create(array_merge($shared, [
        'type' => 'receipt',
        'payment_status' => 'pending',
        'amount' => 0.00,
    ]));

    return [$invoice, $receipt];
}

function createLegacyUserInvoiceLedger(User $user, string $orderReference): Ledger
{
    $feeType = ledgerSearchFeeType(FeeTypeEnum::APPLICATION_FEE);
    $intake = ledgerSearchIntakePeriod();

    [$invoice] = ledgerSearchCreateLedgerPair($user, $feeType, $orderReference, $intake->id, $user->tenant_id);

    return $invoice;
}

function createApplicationFeeInvoiceLedger(User $user, string $orderReference): Ledger
{
    $feeType = ledgerSearchFeeType(FeeTypeEnum::APPLICATION_FEE);
    $intake = ledgerSearchIntakePeriod();
    $level = Level::factory()->create();

    $applicationFee = ApplicationFee::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'intake_period_id' => $intake->id,
        'level_id' => $level->id,
        'status' => ApplicationFeeStatusEnum::AWAITING_PAYMENT,
    ]);

    [$invoice] = ledgerSearchCreateLedgerPair($applicationFee, $feeType, $orderReference, $intake->id, $user->tenant_id);

    return $invoice;
}

function createHostelApplicationInvoiceLedger(StudentApplication $studentApplication, string $orderReference): Ledger
{
    $student = $studentApplication->student;
    $enrolment = StudentEnrolment::query()
        ->where('student_application_id', $studentApplication->id)
        ->latest('id')
        ->first()
        ?? attachHostelApplicationEnrolment($studentApplication);

    $application = HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
        'tenant_id' => $studentApplication->tenant_id,
        'student_id' => $student->id,
        'student_enrolment_id' => $enrolment->id,
        'gender_id' => $student->gender_id,
        'type' => HostelApplicationTypeEnum::STUDENT,
        'status' => HostelApplicationStatusEnum::AWAITING_PAYMENT,
        'next_of_kin_name' => 'Kin',
        'next_of_kin_contact' => '0771234567',
        'check_in' => now()->toDateString(),
        'check_out' => now()->addMonths(4)->toDateString(),
    ]));

    $feeType = ledgerSearchFeeType(FeeTypeEnum::STUDENT_ACCOMMODATION_FEE);
    $intake = $studentApplication->intakePeriod;

    [$invoice] = ledgerSearchCreateLedgerPair(
        $application,
        $feeType,
        $orderReference,
        $intake->id,
        $studentApplication->tenant_id,
    );

    return $invoice;
}

function createTuitionInvoiceLedger(StudentApplication $studentApplication, string $orderReference): Ledger
{
    $feeType = ledgerSearchFeeType(FeeTypeEnum::TUITION_FEE);

    [$invoice] = ledgerSearchCreateLedgerPair(
        $studentApplication,
        $feeType,
        $orderReference,
        $studentApplication->intake_period_id,
        $studentApplication->tenant_id,
    );

    return $invoice;
}

function createStudentIdCardInvoiceLedger(StudentApplication $studentApplication, string $orderReference): Ledger
{
    $request = StudentIdCardRequest::withoutEvents(fn () => StudentIdCardRequest::query()->create([
        'tenant_id' => $studentApplication->tenant_id,
        'student_id' => $studentApplication->student_id,
        'status' => IdCardRequestStatusEnum::AWAITING_PAYMENT,
        'reason' => IdCardRequestReasonEnum::NEW,
    ]));

    $feeType = ledgerSearchFeeType(FeeTypeEnum::STUDENT_ID_FEE);

    [$invoice] = ledgerSearchCreateLedgerPair(
        $request,
        $feeType,
        $orderReference,
        $studentApplication->intake_period_id,
        $studentApplication->tenant_id,
    );

    return $invoice;
}

function ledgerSearchStaffUserFor(StudentApplication $studentApplication): User
{
    $user = User::factory()->create(['tenant_id' => $studentApplication->tenant_id]);
    Permission::findOrCreate('view:payments-debug', 'web');
    $user->givePermissionTo('view:payments-debug');

    return $user;
}

function configureLedgerSearchPaymentGateway(): void
{
    config([
        'custom.payments.payment-gateway.base_url' => 'https://gateway.test',
        'custom.payments.payment-gateway.api_key' => 'test-key',
        'custom.payments.payment-gateway.secret' => 'test-secret',
        'custom.payments.payment-gateway.name' => 'smile-n-pay',
        'custom.payments.payment-gateway.return_url' => 'https://app.test/feedback',
        'custom.payments.payment-gateway.cancel_url' => 'https://app.test/cancel',
        'custom.payments.payment-gateway.failure_url' => 'https://app.test/failure',
        'custom.payments.payment-gateway.result_url' => 'https://app.test/result',
    ]);
}

test('payments debug index is a cheap inertia shell without ledger search', function () {
    $authUser = ledgerSearchAuthUser(['view:payments-debug', 'update:payments-debug']);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($authUser)
        ->get(route('integrations.payments-debug.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('integrations/payments-debug/Index')
            ->where('canUpdate', true)
        );

    $ledgerQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'ledgers'));

    expect($ledgerQueries)->toBeEmpty();
});

test('old payments debug page url redirects to integrations', function () {
    $authUser = ledgerSearchAuthUser();

    $this->actingAs($authUser)
        ->get(route('integrations.payments.check-status-create'))
        ->assertRedirect(route('integrations.payments-debug.index'));
});

test('unauthorized users cannot open payments debug', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('integrations.payments-debug.index'))
        ->assertForbidden();
});

test('staff cannot open payments debug when the integrations module is disabled', function () {
    $user = ledgerSearchAuthUser();

    Module::query()
        ->where('slug', ModuleEnum::INTEGRATIONS->slug())
        ->firstOrFail()
        ->update(['status' => false]);

    app(RbacModuleStateService::class)->clearCache();

    $this->actingAs($user)
        ->get(route('integrations.payments-debug.index'))
        ->assertForbidden();
});

test('view-only users cannot update payment status', function () {
    $user = ledgerSearchAuthUser(['view:payments-debug']);

    $this->actingAs($user)
        ->postJson(route('integrations.payments-debug.update'), [
            'orderReference' => 'ORDER-SEC-1',
            'paymentStatus' => 'paid',
        ])
        ->assertForbidden();
});

test('ledger search by system reference returns invoices', function () {
    $authUser = ledgerSearchAuthUser();
    $targetUser = User::factory()->create(['tenant_id' => $authUser->tenant_id]);
    createLegacyUserInvoiceLedger($targetUser, 'ORDER-REF-001');

    $this->actingAs($authUser)
        ->getJson(route('integrations.payments-debug.search', ['q' => 'ORDER-REF-001']))
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('matchedBy', 'reference')
            ->where('person.email', $targetUser->email)
            ->has('groups', 1)
            ->where('groups.0.type', 'application-fee')
            ->where('groups.0.ledgers.0.attributes.systemReference', 'ORDER-REF-001')
            ->etc()
        );
});

test('ledger search by email with a single type returns invoices immediately', function () {
    $authUser = ledgerSearchAuthUser();
    $targetUser = User::factory()->create([
        'tenant_id' => $authUser->tenant_id,
        'email' => 'legacy-user@example.com',
    ]);

    createLegacyUserInvoiceLedger($targetUser, 'ORDER-LEGACY-001');

    $this->actingAs($authUser)
        ->getJson(route('integrations.payments-debug.search', ['q' => 'legacy-user@example.com']))
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('matchedBy', 'email')
            ->where('person.email', 'legacy-user@example.com')
            ->has('groups', 1)
            ->where('groups.0.type', 'application-fee')
            ->where('groups.0.ledgers.0.attributes.systemReference', 'ORDER-LEGACY-001')
            ->missing('requiresTypeSelection')
            ->etc()
        );
});

test('ledger search by email is case insensitive', function () {
    $authUser = ledgerSearchAuthUser();
    $targetUser = User::factory()->create([
        'tenant_id' => $authUser->tenant_id,
        'email' => 'Case.User@example.com',
    ]);

    createLegacyUserInvoiceLedger($targetUser, 'ORDER-CASE-EMAIL');

    $this->actingAs($authUser)
        ->getJson(route('integrations.payments-debug.search', ['q' => 'case.user@example.com']))
        ->assertOk()
        ->assertJsonPath('matchedBy', 'email')
        ->assertJsonPath('groups.0.ledgers.0.attributes.systemReference', 'ORDER-CASE-EMAIL');
});

test('ledger search by email with a fee type filter returns only that fee type', function () {
    $studentApplication = createStudentReadyForHostelApplication('H25FILTER01');
    $authUser = ledgerSearchStaffUserFor($studentApplication);
    $studentApplication->student->user->update(['email' => 'fee-filter-user@example.com']);

    createTuitionInvoiceLedger($studentApplication, 'ORDER-FILTER-TUITION');
    createHostelApplicationInvoiceLedger($studentApplication, 'ORDER-FILTER-HOSTEL');

    $this->actingAs($authUser)
        ->getJson(route('integrations.payments-debug.search', [
            'q' => 'fee-filter-user@example.com',
            'feeType' => FeeTypeEnum::TUITION_FEE->slug(),
        ]))
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('groups', 1)
            ->where('groups.0.type', 'tuition-fee')
            ->where('groups.0.ledgers.0.attributes.systemReference', 'ORDER-FILTER-TUITION')
            ->etc()
        );
});

test('ledger search by email with a single application fee type returns invoices immediately', function () {
    $authUser = ledgerSearchAuthUser();
    $targetUser = User::factory()->create([
        'tenant_id' => $authUser->tenant_id,
        'email' => 'app-fee-user@example.com',
    ]);

    createApplicationFeeInvoiceLedger($targetUser, 'ORDER-APP-FEE-001');

    $this->actingAs($authUser)
        ->getJson(route('integrations.payments-debug.search', ['q' => 'app-fee-user@example.com']))
        ->assertOk()
        ->assertJsonPath('matchedBy', 'email')
        ->assertJsonPath('groups.0.type', 'application-fee')
        ->assertJsonPath('groups.0.ledgers.0.attributes.systemReference', 'ORDER-APP-FEE-001');
});

test('ledger search by email with a single hostel type returns invoices immediately', function () {
    $studentApplication = createStudentReadyForHostelApplication('LEDGER-SEARCH-HOSTEL');
    $authUser = ledgerSearchStaffUserFor($studentApplication);
    $studentApplication->student->user->update(['email' => 'hostel-user@example.com']);

    createHostelApplicationInvoiceLedger($studentApplication, 'ORDER-HOSTEL-001');

    $this->actingAs($authUser)
        ->getJson(route('integrations.payments-debug.search', ['q' => 'hostel-user@example.com']))
        ->assertOk()
        ->assertJsonPath('matchedBy', 'email')
        ->assertJsonPath('groups.0.type', 'student-accommodation-fee')
        ->assertJsonPath('groups.0.ledgers.0.attributes.systemReference', 'ORDER-HOSTEL-001');
});

test('ledger search returns every fee type a person has paid, grouped by fee type', function () {
    $studentApplication = createStudentReadyForHostelApplication('H25ALLFEES');
    $authUser = ledgerSearchStaffUserFor($studentApplication);

    createTuitionInvoiceLedger($studentApplication, 'ORDER-ALL-TUITION');
    createApplicationFeeInvoiceLedger($studentApplication->student->user, 'ORDER-ALL-APP-FEE');
    createStudentIdCardInvoiceLedger($studentApplication, 'ORDER-ALL-ID-CARD');
    createHostelApplicationInvoiceLedger($studentApplication, 'ORDER-ALL-HOSTEL');

    $this->actingAs($authUser)
        ->getJson(route('integrations.payments-debug.search', ['q' => 'H25ALLFEES']))
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('matchedBy', 'student_number')
            ->has('groups', 4)
            ->where('groups.0.type', 'tuition-fee')
            ->where('groups.1.type', 'application-fee')
            ->where('groups.2.type', 'student-id-fee')
            ->where('groups.3.type', 'student-accommodation-fee')
            ->etc()
        );
});

test('ledger search returns payer programme details and newest invoices first', function () {
    $studentApplication = createStudentReadyForHostelApplication('H25CARD01');
    $authUser = ledgerSearchStaffUserFor($studentApplication);

    $older = createTuitionInvoiceLedger($studentApplication, 'ORDER-CARD-OLD');
    Ledger::query()->whereKey($older->id)->update(['created_at' => now()->subMonths(2)]);
    createTuitionInvoiceLedger($studentApplication, 'ORDER-CARD-NEW');

    $this->actingAs($authUser)
        ->getJson(route('integrations.payments-debug.search', ['q' => 'H25CARD01']))
        ->assertOk()
        ->assertJsonPath('person.studentNumber', 'H25CARD01')
        ->assertJsonPath('person.department', $studentApplication->institutionDepartment->department->name)
        ->assertJsonPath('person.course', $studentApplication->departmentCourse->course->name)
        ->assertJsonPath('person.level', $studentApplication->departmentLevel->level->name)
        ->assertJsonPath('groups.0.ledgers.0.attributes.systemReference', 'ORDER-CARD-NEW')
        ->assertJsonPath('groups.0.ledgers.1.attributes.systemReference', 'ORDER-CARD-OLD');
});

test('ledger search rejects an unknown fee type filter', function () {
    $authUser = ledgerSearchAuthUser();
    $targetUser = User::factory()->create([
        'tenant_id' => $authUser->tenant_id,
        'email' => 'invalid-type-user@example.com',
    ]);

    createApplicationFeeInvoiceLedger($targetUser, 'ORDER-INVALID-TYPE');

    $this->actingAs($authUser)
        ->getJson(route('integrations.payments-debug.search', [
            'q' => 'invalid-type-user@example.com',
            'feeType' => 'not-a-fee-type',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('feeType');
});

test('ledger search returns not found when the fee type filter has no invoices', function () {
    $authUser = ledgerSearchAuthUser();
    $targetUser = User::factory()->create([
        'tenant_id' => $authUser->tenant_id,
        'email' => 'no-tuition-user@example.com',
    ]);

    createApplicationFeeInvoiceLedger($targetUser, 'ORDER-NO-TUITION');
    ledgerSearchFeeType(FeeTypeEnum::TUITION_FEE);

    $this->actingAs($authUser)
        ->getJson(route('integrations.payments-debug.search', [
            'q' => 'no-tuition-user@example.com',
            'feeType' => FeeTypeEnum::TUITION_FEE->slug(),
        ]))
        ->assertNotFound()
        ->assertJsonPath('message', __('integrations.payments_debug_not_found'));
});

test('ledger search returns not found for unknown email', function () {
    $authUser = ledgerSearchAuthUser();

    $this->actingAs($authUser)
        ->getJson(route('integrations.payments-debug.search', ['q' => 'missing-user@example.com']))
        ->assertNotFound()
        ->assertJsonPath('message', __('integrations.payments_debug_not_found'));
});

test('ledger search by student number returns invoices', function () {
    $studentApplication = createStudentReadyForHostelApplication('H25DEBUG01');
    $authUser = User::factory()->create(['tenant_id' => $studentApplication->tenant_id]);
    Permission::findOrCreate('view:payments-debug', 'web');
    $authUser->givePermissionTo('view:payments-debug');

    createHostelApplicationInvoiceLedger($studentApplication, 'ORDER-STUDENT-NUM');

    $this->actingAs($authUser)
        ->getJson(route('integrations.payments-debug.search', ['q' => 'h25debug01']))
        ->assertOk()
        ->assertJsonPath('matchedBy', 'student_number')
        ->assertJsonPath('person.studentNumber', 'H25DEBUG01')
        ->assertJsonPath('groups.0.ledgers.0.attributes.systemReference', 'ORDER-STUDENT-NUM');
});

test('ledger search returns not found for unknown student number', function () {
    $authUser = ledgerSearchAuthUser();

    $this->actingAs($authUser)
        ->getJson(route('integrations.payments-debug.search', ['q' => 'H00MISSING']))
        ->assertNotFound()
        ->assertJsonPath('message', __('integrations.payments_debug_not_found'));
});

test('check status resolves application fee ledger by email', function () {
    configureLedgerSearchPaymentGateway();

    Http::fake([
        'https://gateway.test/payments/transaction/ORDER-CHECK-APP/status/check' => Http::response([
            'status' => 'paid',
        ]),
    ]);

    $authUser = ledgerSearchAuthUser();
    $targetUser = User::factory()->create([
        'tenant_id' => $authUser->tenant_id,
        'email' => 'check-status-app@example.com',
    ]);

    createApplicationFeeInvoiceLedger($targetUser, 'ORDER-CHECK-APP');

    $this->actingAs($authUser)
        ->postJson(route('integrations.payments-debug.check', ['order_reference' => 'check-status-app@example.com']))
        ->assertOk()
        ->assertJsonPath('status', 'paid');
});
