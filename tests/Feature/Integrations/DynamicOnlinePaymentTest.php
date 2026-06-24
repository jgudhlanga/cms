<?php

use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\HMS\HostelApplicationTypeEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Enums\Students\ApplicationFeeStatusEnum;
use App\Helpers\PaymentHelper;
use App\Models\HMS\HostelApplication;
use App\Models\Institution\Level;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\FeeType;
use App\Models\Students\ApplicationFee;
use App\Models\Students\StudentApplication;
use App\Services\HMS\StudentAccommodationFeeService;
use App\Services\Students\ApplicationFeeService;
use Illuminate\Support\Facades\Http;

function paymentTestFeeType(FeeTypeEnum $feeTypeEnum): FeeType
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

function configurePaymentGateway(): void
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

function createAwaitingPaymentHostelApplication(StudentApplication $studentApplication): HostelApplication
{
    $student = $studentApplication->student;
    $enrolment = attachHostelApplicationEnrolment($studentApplication);

    return HostelApplication::withoutEvents(fn () => HostelApplication::query()->create([
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
}

function createLedgerPair(
    object $ledgerable,
    FeeType $feeType,
    string $orderReference,
    int $intakePeriodId,
    int $tenantId,
    string $invoiceStatus = 'pending',
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
        'payment_status' => $invoiceStatus,
        'amount' => 150.00,
    ]));

    $receipt = Ledger::query()->create(array_merge($shared, [
        'type' => 'receipt',
        'payment_status' => $invoiceStatus,
        'amount' => 0.00,
    ]));

    return [$invoice, $receipt];
}

test('initiate accommodation payment creates ledgers on hostel application', function () {
    configurePaymentGateway();

    $studentApplication = createStudentReadyForHostelApplication('PAY-HMS-01');
    $user = $studentApplication->student->user;
    $application = createAwaitingPaymentHostelApplication($studentApplication);
    $feeType = paymentTestFeeType(FeeTypeEnum::STUDENT_ACCOMMODATION_FEE);
    $orderReference = 'ORD-ACC-001';

    Http::fake([
        'gateway.test/payments/initiate-transaction' => Http::response([
            'paymentUrl' => 'https://pay.test/checkout',
            'transactionReference' => 'TXN-ACC-001',
            'responseCode' => '00',
            'responseMessage' => 'OK',
        ]),
    ]);

    $this->actingAs($user)
        ->postJson(route('integrations.payments.initiate'), [
            'orderReference' => $orderReference,
            'feeTypeId' => $feeType->id,
            'ledgerableId' => $application->id,
            'amount' => 150,
            'itemName' => 'Student Accommodation Fee',
            'itemDescription' => 'Student Accommodation Fee',
            'currencyCode' => '840',
            'firstName' => 'Test',
            'lastName' => 'Student',
            'email' => $user->email,
        ])
        ->assertSuccessful()
        ->assertJsonPath('paymentUrl', 'https://pay.test/checkout');

    expect(Ledger::query()->where('system_reference', $orderReference)->count())->toBe(2);
    expect(Ledger::query()
        ->where('system_reference', $orderReference)
        ->where('ledgerable_type', HostelApplication::class)
        ->where('ledgerable_id', $application->id)
        ->count())->toBe(2);
});

test('feedback updates accommodation ledgers by order reference', function () {
    configurePaymentGateway();

    $studentApplication = createStudentReadyForHostelApplication('PAY-HMS-02');
    $user = $studentApplication->student->user;
    $application = createAwaitingPaymentHostelApplication($studentApplication);
    $feeType = paymentTestFeeType(FeeTypeEnum::STUDENT_ACCOMMODATION_FEE);
    $orderReference = 'ORD-ACC-002';

    [, $receipt] = createLedgerPair(
        $application,
        $feeType,
        $orderReference,
        $studentApplication->intake_period_id,
        $studentApplication->tenant_id,
    );

    Http::fake([
        'gateway.test/payments/transaction/*/status/check' => Http::response([
            'status' => 'paid',
            'paymentOption' => 'card',
            'createdDate' => now()->toDateString(),
            'amount' => 150,
            'orderReference' => $orderReference,
            'reference' => 'PAY-REF-002',
            'currency' => 'USD',
            'clientFee' => 0,
            'merchantFee' => 0,
        ]),
    ]);

    $this->actingAs($user)
        ->get(route('integrations.payments.feedback', ['orderReference' => $orderReference]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('integrations/payments/Feedback')
            ->where('isAccommodationFee', true)
            ->where('details.attributes.paymentStatus', 'paid'));

    expect($receipt->fresh()->payment_status)->toBe('paid');
    expect((float) $receipt->fresh()->amount)->toBe(150.0);

    $application = $application->fresh();
    expect($application->status)->toBe(HostelApplicationStatusEnum::PAID);
    expect($application->payment_verification['accommodation_fees_paid_confirmed'] ?? false)->toBeTrue();
});

test('delete not paid ledger entries is scoped by fee type', function () {
    $studentApplication = createVerifiedStudentApplication('PAY-SCOPE-01');
    $user = $studentApplication->student->user;
    $applicationFeeType = paymentTestFeeType(FeeTypeEnum::APPLICATION_FEE);
    $accommodationFeeType = paymentTestFeeType(FeeTypeEnum::STUDENT_ACCOMMODATION_FEE);
    $level = Level::query()->where('has_application_fee_payment', true)->first()
        ?? Level::query()->firstOrFail();

    $applicationFee = ApplicationFee::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'intake_period_id' => $studentApplication->intake_period_id,
        'level_id' => $level->id,
        'status' => ApplicationFeeStatusEnum::AWAITING_PAYMENT,
    ]);

    createLedgerPair(
        $applicationFee,
        $applicationFeeType,
        'ORD-APP-PENDING',
        $studentApplication->intake_period_id,
        $studentApplication->tenant_id,
    );

    [$paidInvoice] = createLedgerPair(
        $user,
        $accommodationFeeType,
        'ORD-ACC-PAID',
        $studentApplication->intake_period_id,
        $studentApplication->tenant_id,
        'paid',
    );

    createLedgerPair(
        $user,
        $accommodationFeeType,
        'ORD-ACC-OLD-PENDING',
        $studentApplication->intake_period_id,
        $studentApplication->tenant_id,
    );

    PaymentHelper::deleteNotPaidLedgerEntries($paidInvoice->system_reference);

    expect(Ledger::query()->where('system_reference', 'ORD-APP-PENDING')->count())->toBe(2);
    expect(Ledger::query()->where('system_reference', 'ORD-ACC-OLD-PENDING')->count())->toBe(0);
    expect(Ledger::onlyTrashed()->where('system_reference', 'ORD-ACC-OLD-PENDING')->count())->toBe(2);
    expect(Ledger::query()->where('system_reference', 'ORD-ACC-PAID')->count())->toBe(2);
});

test('student accommodation fee service reports fully paid from hostel application receipt', function () {
    $studentApplication = createStudentReadyForHostelApplication('PAY-HMS-03');
    $application = createAwaitingPaymentHostelApplication($studentApplication);
    $feeType = paymentTestFeeType(FeeTypeEnum::STUDENT_ACCOMMODATION_FEE);
    $orderReference = 'ORD-ACC-003';

    [, $receipt] = createLedgerPair(
        $application,
        $feeType,
        $orderReference,
        $studentApplication->intake_period_id,
        $studentApplication->tenant_id,
        'paid',
    );

    $receipt->update(['amount' => 150, 'payment_status' => 'paid']);

    $summary = app(StudentAccommodationFeeService::class)
        ->summaryForStudent($studentApplication->student->fresh());

    expect($summary['isFullyPaid'])->toBeTrue();
    expect($application->fresh()->hasPaidAccommodationFee())->toBeTrue();
    expect($application->fresh()->status)->toBe(HostelApplicationStatusEnum::PAID);
    expect($application->fresh()->payment_verification['accommodation_fees_paid_confirmed'] ?? false)->toBeTrue();
});

test('direct receipt update syncs hostel application to paid via ledger observer', function () {
    $studentApplication = createStudentReadyForHostelApplication('PAY-HMS-06');
    $application = createAwaitingPaymentHostelApplication($studentApplication);
    $feeType = paymentTestFeeType(FeeTypeEnum::STUDENT_ACCOMMODATION_FEE);
    $orderReference = 'ORD-ACC-006';

    [, $receipt] = createLedgerPair(
        $application,
        $feeType,
        $orderReference,
        $studentApplication->intake_period_id,
        $studentApplication->tenant_id,
    );

    $receipt->update(['amount' => 150, 'payment_status' => 'paid']);

    $application = $application->fresh();

    expect($application->status)->toBe(HostelApplicationStatusEnum::PAID);
    expect($application->payment_verification['accommodation_fees_paid_confirmed'] ?? false)->toBeTrue();
});

test('partial accommodation receipt syncs hostel application to partially paid', function () {
    $studentApplication = createStudentReadyForHostelApplication('PAY-HMS-07');
    $application = createAwaitingPaymentHostelApplication($studentApplication);
    $feeType = paymentTestFeeType(FeeTypeEnum::STUDENT_ACCOMMODATION_FEE);
    $orderReference = 'ORD-ACC-007';

    [, $receipt] = createLedgerPair(
        $application,
        $feeType,
        $orderReference,
        $studentApplication->intake_period_id,
        $studentApplication->tenant_id,
    );

    $receipt->update(['amount' => 75, 'payment_status' => 'paid']);

    $application = $application->fresh();

    expect($application->status)->toBe(HostelApplicationStatusEnum::PARTIALLY_PAID);
    expect($application->payment_verification['accommodation_fees_paid_confirmed'] ?? false)->toBeFalse();
});

test('remaining accommodation receipt balance syncs hostel application to paid', function () {
    $studentApplication = createStudentReadyForHostelApplication('PAY-HMS-08');
    $application = createAwaitingPaymentHostelApplication($studentApplication);
    $feeType = paymentTestFeeType(FeeTypeEnum::STUDENT_ACCOMMODATION_FEE);
    $orderReference = 'ORD-ACC-008';

    [, $receipt] = createLedgerPair(
        $application,
        $feeType,
        $orderReference,
        $studentApplication->intake_period_id,
        $studentApplication->tenant_id,
    );

    $receipt->update(['amount' => 75, 'payment_status' => 'paid']);
    expect($application->fresh()->status)->toBe(HostelApplicationStatusEnum::PARTIALLY_PAID);

    $receipt->fresh()->update(['amount' => 150]);

    $application = $application->fresh();

    expect($application->status)->toBe(HostelApplicationStatusEnum::PAID);
    expect($application->payment_verification['accommodation_fees_paid_confirmed'] ?? false)->toBeTrue();
});

test('application fee feedback still resolves by order reference', function () {
    configurePaymentGateway();

    $studentApplication = createVerifiedStudentApplication('PAY-APP-01');
    $user = $studentApplication->student->user;
    $feeType = paymentTestFeeType(FeeTypeEnum::APPLICATION_FEE);
    $orderReference = 'ORD-APP-001';
    $level = Level::query()->where('has_application_fee_payment', true)->first()
        ?? Level::query()->firstOrFail();

    $applicationFee = ApplicationFee::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'intake_period_id' => $studentApplication->intake_period_id,
        'level_id' => $level->id,
        'status' => ApplicationFeeStatusEnum::AWAITING_PAYMENT,
    ]);

    [, $receipt] = createLedgerPair(
        $applicationFee,
        $feeType,
        $orderReference,
        $studentApplication->intake_period_id,
        $studentApplication->tenant_id,
    );

    Http::fake([
        'gateway.test/payments/transaction/*/status/check' => Http::response([
            'status' => 'paid',
            'paymentOption' => 'card',
            'createdDate' => now()->toDateString(),
            'amount' => 20,
            'orderReference' => $orderReference,
            'reference' => 'PAY-REF-APP',
            'currency' => 'USD',
            'clientFee' => 0,
            'merchantFee' => 0,
        ]),
    ]);

    $this->actingAs($user)
        ->get(route('integrations.payments.feedback', ['orderReference' => $orderReference]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('integrations/payments/Feedback')
            ->where('isApplicationFee', true)
            ->where('details.attributes.paymentStatus', 'paid'));

    expect($receipt->fresh()->payment_status)->toBe('paid');
});

test('portal accommodation fees endpoint works after paid receipt has payment date', function () {
    configurePaymentGateway();

    $studentApplication = createStudentReadyForHostelApplication('PAY-HMS-04');
    $user = $studentApplication->student->user;
    $user->givePermissionTo('manageOwnStudentAccommodationDetails:students');
    $application = createAwaitingPaymentHostelApplication($studentApplication);
    $feeType = paymentTestFeeType(FeeTypeEnum::STUDENT_ACCOMMODATION_FEE);
    $orderReference = 'ORD-ACC-004';

    [, $receipt] = createLedgerPair(
        $application,
        $feeType,
        $orderReference,
        $studentApplication->intake_period_id,
        $studentApplication->tenant_id,
    );

    Http::fake([
        'gateway.test/payments/transaction/*/status/check' => Http::response([
            'status' => 'paid',
            'paymentOption' => 'card',
            'createdDate' => now()->toDateString(),
            'amount' => 150,
            'orderReference' => $orderReference,
            'reference' => 'PAY-REF-004',
            'currency' => 'USD',
            'clientFee' => 0,
            'merchantFee' => 0,
        ]),
    ]);

    $this->actingAs($user)
        ->get(route('integrations.payments.feedback', ['orderReference' => $orderReference]))
        ->assertOk();

    $application = $application->fresh();
    expect($application->status)->toBe(HostelApplicationStatusEnum::PAID);
    expect($application->payment_verification['accommodation_fees_paid_confirmed'] ?? false)->toBeTrue();

    $this->actingAs($user)
        ->get(route('integrations.payments.feedback', ['orderReference' => $orderReference]))
        ->assertOk();

    expect($application->fresh()->status)->toBe(HostelApplicationStatusEnum::PAID);

    $this->actingAs($user)
        ->getJson(route('v1.json.hostel-applications.accommodationFees', [
            'filter' => ['student' => (string) $studentApplication->student_id],
        ]))
        ->assertSuccessful()
        ->assertJsonPath('meta.isFullyPaid', true)
        ->assertJsonCount(1, 'meta.paymentHistory');
});

test('accommodation fees endpoint syncs stale awaiting payment application after paid receipt', function () {
    $studentApplication = createStudentReadyForHostelApplication('PAY-HMS-09');
    $user = $studentApplication->student->user;
    $user->givePermissionTo('manageOwnStudentAccommodationDetails:students');
    $application = createAwaitingPaymentHostelApplication($studentApplication);
    $feeType = paymentTestFeeType(FeeTypeEnum::STUDENT_ACCOMMODATION_FEE);

    Ledger::withoutEvents(fn () => createLedgerPair(
        $application,
        $feeType,
        'ORD-ACC-009',
        $studentApplication->intake_period_id,
        $studentApplication->tenant_id,
        'paid',
    ));

    expect($application->fresh()->status)->toBe(HostelApplicationStatusEnum::AWAITING_PAYMENT);

    $this->actingAs($user)
        ->getJson(route('v1.json.hostel-applications.accommodationFees', [
            'filter' => ['student' => (string) $studentApplication->student_id],
        ]))
        ->assertSuccessful()
        ->assertJsonPath('meta.isFullyPaid', true);

    expect($application->fresh()->status)->toBe(HostelApplicationStatusEnum::PAID);
    expect($application->fresh()->payment_verification['accommodation_fees_paid_confirmed'] ?? false)->toBeTrue();
});

test('initiate accommodation payment rejects paid hostel application', function () {
    configurePaymentGateway();

    $studentApplication = createStudentReadyForHostelApplication('PAY-HMS-05');
    $user = $studentApplication->student->user;
    $application = createAwaitingPaymentHostelApplication($studentApplication);
    $application->update(['status' => HostelApplicationStatusEnum::PAID]);
    $feeType = paymentTestFeeType(FeeTypeEnum::STUDENT_ACCOMMODATION_FEE);

    Http::fake([
        'gateway.test/payments/initiate-transaction' => Http::response([
            'paymentUrl' => 'https://pay.test/checkout',
            'transactionReference' => 'TXN-ACC-005',
            'responseCode' => '00',
            'responseMessage' => 'OK',
        ]),
    ]);

    $this->actingAs($user)
        ->postJson(route('integrations.payments.initiate'), [
            'orderReference' => 'ORD-ACC-005',
            'feeTypeId' => $feeType->id,
            'ledgerableId' => $application->id,
            'amount' => 150,
            'itemName' => 'Student Accommodation Fee',
            'itemDescription' => 'Student Accommodation Fee',
            'currencyCode' => '840',
            'firstName' => 'Test',
            'lastName' => 'Student',
            'email' => $user->email,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['ledgerableId']);
});
