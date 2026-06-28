<?php

use App\Enums\Acl\RoleEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Enums\Shared\TenantEnum;
use App\Enums\Students\ApplicationFeeStatusEnum;
use App\Helpers\PaymentHelper;
use App\Models\Acl\Role;
use App\Models\Institution\FeeStructure;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\FeeType;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\ApplicationFee;
use App\Models\Students\Student;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Services\Students\ApplicationFeeService;

beforeEach(function () {
    Role::findOrCreate(RoleEnum::STUDENT->name(), 'web');
});

function createApplicationFeeLedgerPair(
    ApplicationFee $applicationFee,
    FeeType $feeType,
    string $orderReference,
    int $intakePeriodId,
    int $tenantId,
    string $invoiceStatus = 'pending',
): array {
    $shared = [
        'tenant_id' => $tenantId,
        'ledgerable_type' => ApplicationFee::class,
        'ledgerable_id' => $applicationFee->id,
        'fee_type_id' => $feeType->id,
        'system_reference' => $orderReference,
        'intake_period_id' => $intakePeriodId,
        'payment_gateway' => 'smile-n-pay',
    ];

    $invoice = Ledger::query()->create(array_merge($shared, [
        'type' => 'invoice',
        'payment_status' => $invoiceStatus,
        'amount' => 20.00,
    ]));

    $receipt = Ledger::query()->create(array_merge($shared, [
        'type' => 'receipt',
        'payment_status' => $invoiceStatus,
        'amount' => 0.00,
    ]));

    return [$invoice, $receipt];
}

function createPortalUserWithoutProfile(): User
{
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->make([
        'tenant_id' => $tenant->id,
        'email_verified_at' => now(),
    ]);
    $user->password = 'Password1!';
    $user->save();
    $user->assignRole(RoleEnum::STUDENT->name());
    $user->givePermissionTo('manageOwnStudentPersonalDetails:students');

    return $user;
}

function createTestIntakePeriod(): IntakePeriod
{
    return ensureCurrentIntakeStatus(\App\Enums\Institution\IntakePeriodStatusEnum::Open->value);
}

function feeRequiredLevel(): Level
{
    return Level::factory()->create([
        'has_application_fee_payment' => true,
        'show_on_current_application_period' => true,
    ]);
}

function feeFreeLevel(): Level
{
    return Level::factory()->create([
        'has_application_fee_payment' => false,
        'show_on_current_application_period' => true,
    ]);
}

test('selecting fee required level creates one application fee per intake', function () {
    $user = createPortalUserWithoutProfile();
    $level = feeRequiredLevel();
    $intake = createTestIntakePeriod();

    session([
        'registration.id_number' => '55-1234567C55',
    ]);

    $this->actingAs($user)
        ->post(route('portal.application.select-level'), [
            'level_id' => $level->id,
            'intake_period_id' => $intake->id,
        ])
        ->assertRedirect(route('portal.application.fee-payment'));

    expect(ApplicationFee::query()->where('user_id', $user->id)->count())->toBe(1);

    $this->actingAs($user)
        ->post(route('portal.application.select-level'), [
            'level_id' => $level->id,
            'intake_period_id' => $intake->id,
        ])
        ->assertRedirect(route('portal.application.fee-payment'));

    expect(ApplicationFee::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('selecting level without fee does not create application fee record', function () {
    ensureCurrentIntakeStatus(\App\Enums\Institution\IntakePeriodStatusEnum::Open->value);
    $user = createPortalUserWithoutProfile();
    $level = feeFreeLevel();

    $this->actingAs($user)
        ->post(route('portal.application.select-level'), ['level_id' => $level->id])
        ->assertRedirect(route('portal.application.create'));

    expect(ApplicationFee::query()->where('user_id', $user->id)->exists())->toBeFalse();
});

test('switching from fee-required to fee-free level abandons unpaid application fee', function () {
    $user = createPortalUserWithoutProfile();
    $feeRequired = feeRequiredLevel();
    $feeFree = feeFreeLevel();
    $intake = createTestIntakePeriod();

    $this->actingAs($user)
        ->post(route('portal.application.select-level'), [
            'level_id' => $feeRequired->id,
            'intake_period_id' => $intake->id,
        ])
        ->assertRedirect(route('portal.application.fee-payment'));

    $applicationFee = ApplicationFee::query()->where('user_id', $user->id)->first();
    expect($applicationFee)->not->toBeNull()
        ->and($applicationFee->status)->toBe(ApplicationFeeStatusEnum::AWAITING_PAYMENT);

    $this->actingAs($user)
        ->post(route('portal.application.select-level'), ['level_id' => $feeFree->id])
        ->assertRedirect(route('portal.application.create'));

    expect($applicationFee->fresh()->status)->toBe(ApplicationFeeStatusEnum::CANCELLED);
    expect(app(ApplicationFeeService::class)->activeApplicationFee($user))->toBeNull();
});

test('unpaid fee student can access level options to change level', function () {
    $user = createPortalUserWithoutProfile();
    $feeRequired = feeRequiredLevel();
    $intake = createTestIntakePeriod();

    $this->actingAs($user)
        ->post(route('portal.application.select-level'), [
            'level_id' => $feeRequired->id,
            'intake_period_id' => $intake->id,
        ])
        ->assertRedirect(route('portal.application.fee-payment'));

    $this->actingAs($user)
        ->get(route('portal.application.level-options'))
        ->assertOk();
});

test('middleware sends fee-free level student to application create after level switch', function () {
    $user = createPortalUserWithoutProfile();
    $feeRequired = feeRequiredLevel();
    $feeFree = feeFreeLevel();
    $intake = createTestIntakePeriod();

    $this->actingAs($user)
        ->post(route('portal.application.select-level'), [
            'level_id' => $feeRequired->id,
            'intake_period_id' => $intake->id,
        ])
        ->assertRedirect(route('portal.application.fee-payment'));

    $this->actingAs($user)
        ->post(route('portal.application.select-level'), ['level_id' => $feeFree->id])
        ->assertRedirect(route('portal.application.create'));

    $this->actingAs($user)
        ->get(route('portal.application.fee-payment'))
        ->assertRedirect(route('portal.application.create'));
});

test('login redirects unpaid application fee student to fee payment', function () {
    $user = createPortalUserWithoutProfile();
    $user->forceFill(['email_verified_at' => now()])->save();
    $level = feeRequiredLevel();
    $intake = createTestIntakePeriod();

    ApplicationFee::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'intake_period_id' => $intake->id,
        'level_id' => $level->id,
        'status' => ApplicationFeeStatusEnum::AWAITING_PAYMENT,
    ]);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'Password1!',
    ])->assertRedirect(route('portal.application.fee-payment'));
});

test('fee payment page exposes human readable application fee status label', function () {
    $user = createPortalUserWithoutProfile();
    $level = feeRequiredLevel();
    $intake = createTestIntakePeriod();
    $feeType = FeeType::query()->firstOrCreate(
        ['slug' => FeeTypeEnum::APPLICATION_FEE->slug()],
        [
            'name' => FeeTypeEnum::APPLICATION_FEE->name(),
            'description' => FeeTypeEnum::APPLICATION_FEE->description(),
            'position' => FeeTypeEnum::APPLICATION_FEE->position(),
        ],
    );

    FeeStructure::query()->create([
        'tenant_id' => $user->tenant_id,
        'fee_type_id' => $feeType->id,
        'level_id' => $level->id,
        'mode_of_study_id' => null,
        'amount' => 20.00,
        'local_fca_amount' => 20.00,
    ]);

    ApplicationFee::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'intake_period_id' => $intake->id,
        'level_id' => $level->id,
        'status' => ApplicationFeeStatusEnum::AWAITING_PAYMENT,
    ]);

    $this->actingAs($user)
        ->get(route('portal.application.fee-payment'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/application/RegistrationFeePaymentOptions')
            ->where('applicationFeeStatus', ApplicationFeeStatusEnum::AWAITING_PAYMENT->value)
            ->where('applicationFeeStatusLabel', 'Awaiting payment')
        );
});

test('guest lookup does not expose login email for duplicate records', function () {
    // reuse existing student factory from guest test
    $tenantId = TenantEnum::HARARE_POLY->id();
    $title = Title::query()->firstOrCreate(['name' => 'Mr Guest AF']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male Guest AF']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single Guest AF']);
    $idType = IdType::query()->firstOrCreate(['name' => 'National ID Guest AF']);

    $user = User::factory()->create([
        'tenant_id' => $tenantId,
        'email' => 'hidden.email@example.com',
    ]);

    Student::query()->create([
        'tenant_id' => $tenantId,
        'user_id' => $user->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'id_type_id' => $idType->id,
        'id_number' => '77-1234567D77',
        'date_of_birth' => '2000-01-01',
    ]);

    $response = $this->postJson('/api/v1/guest/enrollment/check-national-id', [
        'id_number' => '77-1234567D77',
    ]);

    $response->assertOk()
        ->assertJson([
            'found' => true,
            'loginEmail' => null,
        ]);
});

test('paid application fee receipt syncs application fee status', function () {
    $user = createPortalUserWithoutProfile();
    $level = feeRequiredLevel();
    $intake = createTestIntakePeriod();
    $feeType = FeeType::query()->firstOrCreate(
        ['slug' => FeeTypeEnum::APPLICATION_FEE->slug()],
        [
            'name' => FeeTypeEnum::APPLICATION_FEE->name(),
            'description' => FeeTypeEnum::APPLICATION_FEE->description(),
            'position' => FeeTypeEnum::APPLICATION_FEE->position(),
        ],
    );

    $applicationFee = ApplicationFee::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'intake_period_id' => $intake->id,
        'level_id' => $level->id,
        'status' => ApplicationFeeStatusEnum::AWAITING_PAYMENT,
    ]);

    [, $receipt] = createApplicationFeeLedgerPair(
        $applicationFee,
        $feeType,
        'ORD-APP-STATUS',
        $intake->id,
        $user->tenant_id,
        'paid',
    );

    $receipt->update(['amount' => 20, 'payment_status' => 'paid']);

    expect($applicationFee->fresh()->status)->toBe(ApplicationFeeStatusEnum::PAID);
});

test('has paid application fee and not applied uses application fee record', function () {
    $user = createPortalUserWithoutProfile();
    $level = feeRequiredLevel();
    $intake = createTestIntakePeriod();

    ApplicationFee::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'intake_period_id' => $intake->id,
        'level_id' => $level->id,
        'status' => ApplicationFeeStatusEnum::PAID,
    ]);

    expect(PaymentHelper::hasPaidApplicationFeeAndNotApplied($user, $intake))->toBeTrue();
});

test('payment result webhook updates ledger records', function () {
    $user = createPortalUserWithoutProfile();
    $level = feeRequiredLevel();
    $intake = createTestIntakePeriod();
    $feeType = FeeType::query()->firstOrCreate(
        ['slug' => FeeTypeEnum::APPLICATION_FEE->slug()],
        [
            'name' => FeeTypeEnum::APPLICATION_FEE->name(),
            'description' => FeeTypeEnum::APPLICATION_FEE->description(),
            'position' => FeeTypeEnum::APPLICATION_FEE->position(),
        ],
    );

    $applicationFee = ApplicationFee::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'intake_period_id' => $intake->id,
        'level_id' => $level->id,
        'status' => ApplicationFeeStatusEnum::AWAITING_PAYMENT,
    ]);

    [, $receipt] = createApplicationFeeLedgerPair(
        $applicationFee,
        $feeType,
        'ORD-WEBHOOK-001',
        $intake->id,
        $user->tenant_id,
    );

    $this->postJson(route('integrations.payments.result'), [
        'orderReference' => 'ORD-WEBHOOK-001',
        'paymentStatus' => 'paid',
        'amount' => 20,
        'currency' => 'USD',
        'createdDate' => now()->toDateString(),
        'paymentReference' => 'PAY-WH-001',
        'paymentOption' => 'card',
        'clientFee' => 0,
        'merchantFee' => 0,
    ], [
        'x-api-key' => config('custom.payments.payment-gateway.api_key'),
        'x-api-secret' => config('custom.payments.payment-gateway.secret'),
    ])->assertOk();

    expect($receipt->fresh()->payment_status)->toBe('paid');
    expect($applicationFee->fresh()->status)->toBe(ApplicationFeeStatusEnum::PAID);
});

test('create application page reports paid status from application fee not pending receipt', function () {
    $user = createPortalUserWithoutProfile();
    $level = feeRequiredLevel();
    $intake = createTestIntakePeriod();

    ApplicationFee::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'intake_period_id' => $intake->id,
        'level_id' => $level->id,
        'status' => ApplicationFeeStatusEnum::PAID,
    ]);

    session(['application.level_id' => $level->id]);

    $this->actingAs($user)
        ->get(route('portal.application.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/application/CreateApplication')
            ->where('hasPaidApplicationFee', true));
});

test('application fee service resolves unpaid record for current intake', function () {
    $user = createPortalUserWithoutProfile();
    $level = feeRequiredLevel();
    $intake = createTestIntakePeriod();

    ApplicationFee::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'intake_period_id' => $intake->id,
        'level_id' => $level->id,
        'status' => ApplicationFeeStatusEnum::AWAITING_PAYMENT,
    ]);

    expect(app(ApplicationFeeService::class)->unpaidForCurrentIntake($user))->not->toBeNull();
});

test('login redirects paid application fee student to application create', function () {
    $user = createPortalUserWithoutProfile();
    $level = feeRequiredLevel();
    $intake = createTestIntakePeriod();

    ApplicationFee::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'intake_period_id' => $intake->id,
        'level_id' => $level->id,
        'status' => ApplicationFeeStatusEnum::PAID,
    ]);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'Password1!',
    ])->assertRedirect(route('portal.application.create'));
});

test('failed application fee receipt syncs back to awaiting payment', function () {
    $user = createPortalUserWithoutProfile();
    $level = feeRequiredLevel();
    $intake = createTestIntakePeriod();
    $feeType = FeeType::query()->firstOrCreate(
        ['slug' => FeeTypeEnum::APPLICATION_FEE->slug()],
        [
            'name' => FeeTypeEnum::APPLICATION_FEE->name(),
            'description' => FeeTypeEnum::APPLICATION_FEE->description(),
            'position' => FeeTypeEnum::APPLICATION_FEE->position(),
        ],
    );

    $applicationFee = ApplicationFee::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'intake_period_id' => $intake->id,
        'level_id' => $level->id,
        'status' => ApplicationFeeStatusEnum::AWAITING_PAYMENT,
    ]);

    [, $receipt] = createApplicationFeeLedgerPair(
        $applicationFee,
        $feeType,
        'ORD-APP-FAIL',
        $intake->id,
        $user->tenant_id,
        'failed',
    );

    $receipt->update(['payment_status' => 'failed']);

    expect($applicationFee->fresh()->status)->toBe(ApplicationFeeStatusEnum::AWAITING_PAYMENT);
});

test('payment result webhook rejects invalid credentials when configured', function () {
    config([
        'custom.payments.payment-gateway.api_key' => 'test-key',
        'custom.payments.payment-gateway.secret' => 'test-secret',
    ]);

    $this->postJson(route('integrations.payments.result'), [
        'orderReference' => 'ORD-INVALID',
        'paymentStatus' => 'paid',
    ])->assertUnauthorized();
});
