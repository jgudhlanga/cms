<?php

use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\HMS\HostelApplicationTypeEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Enums\Students\ApplicationFeeStatusEnum;
use App\Models\HMS\HostelApplication;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\FeeType;
use App\Models\Students\ApplicationFee;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Users\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\Fluent\AssertableJson;

function ledgerSearchAuthUser(): User
{
    return User::factory()->create();
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
    return ensureCurrentIntakeStatus(\App\Enums\Institution\IntakePeriodStatusEnum::Open->value);
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

test('ledger search by system reference returns invoices', function () {
    $authUser = ledgerSearchAuthUser();
    $targetUser = User::factory()->create(['tenant_id' => $authUser->tenant_id]);
    createLegacyUserInvoiceLedger($targetUser, 'ORDER-REF-001');

    $this->actingAs($authUser)
        ->getJson(route('integrations.payments.ledger-entries', ['search' => 'ORDER-REF-001']))
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('0.id')
            ->where('0.attributes.systemReference', 'ORDER-REF-001')
            ->etc()
        );
});

test('ledger search by email with single legacy type prompts type selection', function () {
    $authUser = ledgerSearchAuthUser();
    $targetUser = User::factory()->create([
        'tenant_id' => $authUser->tenant_id,
        'email' => 'legacy-user@example.com',
    ]);

    createLegacyUserInvoiceLedger($targetUser, 'ORDER-LEGACY-001');

    $this->actingAs($authUser)
        ->getJson(route('integrations.payments.ledger-entries', ['search' => 'legacy-user@example.com']))
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('requiresTypeSelection', true)
            ->has('types', 1)
            ->where('types.0.value', 'legacy')
            ->etc()
        );
});

test('ledger search by email with legacy type param returns invoices', function () {
    $authUser = ledgerSearchAuthUser();
    $targetUser = User::factory()->create([
        'tenant_id' => $authUser->tenant_id,
        'email' => 'legacy-user-typed@example.com',
    ]);

    createLegacyUserInvoiceLedger($targetUser, 'ORDER-LEGACY-TYPED-001');

    $this->actingAs($authUser)
        ->getJson(route('integrations.payments.ledger-entries', [
            'search' => 'legacy-user-typed@example.com',
            'ledgerableType' => 'legacy',
        ]))
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('0.id')
            ->where('0.attributes.systemReference', 'ORDER-LEGACY-TYPED-001')
            ->etc()
        );
});

test('ledger search by email with single application fee type prompts type selection', function () {
    $authUser = ledgerSearchAuthUser();
    $targetUser = User::factory()->create([
        'tenant_id' => $authUser->tenant_id,
        'email' => 'app-fee-user@example.com',
    ]);

    createApplicationFeeInvoiceLedger($targetUser, 'ORDER-APP-FEE-001');

    $this->actingAs($authUser)
        ->getJson(route('integrations.payments.ledger-entries', ['search' => 'app-fee-user@example.com']))
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('requiresTypeSelection', true)
            ->has('types', 1)
            ->where('types.0.value', 'application_fee')
            ->etc()
        );
});

test('ledger search by email with single hostel type prompts type selection', function () {
    $studentApplication = createStudentReadyForHostelApplication('LEDGER-SEARCH-HOSTEL');
    $authUser = User::factory()->create(['tenant_id' => $studentApplication->tenant_id]);
    $targetUser = $studentApplication->student->user;
    $targetUser->update(['email' => 'hostel-user@example.com']);

    createHostelApplicationInvoiceLedger($studentApplication, 'ORDER-HOSTEL-001');

    $this->actingAs($authUser)
        ->getJson(route('integrations.payments.ledger-entries', ['search' => 'hostel-user@example.com']))
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('requiresTypeSelection', true)
            ->has('types', 1)
            ->where('types.0.value', 'hostel_application')
            ->etc()
        );
});

test('ledger search by email requires type selection when multiple ledgerable types exist', function () {
    $authUser = ledgerSearchAuthUser();
    $targetUser = User::factory()->create([
        'tenant_id' => $authUser->tenant_id,
        'email' => 'multi-type-user@example.com',
    ]);

    createLegacyUserInvoiceLedger($targetUser, 'ORDER-MULTI-LEGACY');
    createApplicationFeeInvoiceLedger($targetUser, 'ORDER-MULTI-APP');

    $this->actingAs($authUser)
        ->getJson(route('integrations.payments.ledger-entries', ['search' => 'multi-type-user@example.com']))
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('requiresTypeSelection', true)
            ->has('types', 2)
            ->where('types.0.value', 'legacy')
            ->where('types.1.value', 'application_fee')
            ->etc()
        );
});

test('ledger search by email with ledgerable type returns only selected invoices', function () {
    $authUser = ledgerSearchAuthUser();
    $targetUser = User::factory()->create([
        'tenant_id' => $authUser->tenant_id,
        'email' => 'typed-search-user@example.com',
    ]);

    createLegacyUserInvoiceLedger($targetUser, 'ORDER-TYPED-LEGACY');
    createApplicationFeeInvoiceLedger($targetUser, 'ORDER-TYPED-APP');

    $this->actingAs($authUser)
        ->getJson(route('integrations.payments.ledger-entries', [
            'search' => 'typed-search-user@example.com',
            'ledgerableType' => 'application_fee',
        ]))
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('0.id')
            ->where('0.attributes.systemReference', 'ORDER-TYPED-APP')
            ->etc()
        );
});

test('ledger search rejects invalid ledgerable type for email', function () {
    $authUser = ledgerSearchAuthUser();
    $targetUser = User::factory()->create([
        'tenant_id' => $authUser->tenant_id,
        'email' => 'invalid-type-user@example.com',
    ]);

    createApplicationFeeInvoiceLedger($targetUser, 'ORDER-INVALID-TYPE');

    $this->actingAs($authUser)
        ->getJson(route('integrations.payments.ledger-entries', [
            'search' => 'invalid-type-user@example.com',
            'ledgerableType' => 'hostel_application',
        ]))
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Invalid ledgerable type for the provided search.');
});

test('ledger search returns not found for unknown email', function () {
    $authUser = ledgerSearchAuthUser();

    $this->actingAs($authUser)
        ->getJson(route('integrations.payments.ledger-entries', ['search' => 'missing-user@example.com']))
        ->assertNotFound()
        ->assertJsonPath('message', 'No ledger entries found for the provided search missing-user@example.com');
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
        ->postJson(route('integrations.payments.check-status', ['order_reference' => 'check-status-app@example.com']))
        ->assertOk()
        ->assertJsonPath('status', 'paid');
});
