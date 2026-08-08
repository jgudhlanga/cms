<?php

use App\Enums\Shared\FeeTypeEnum;
use App\Http\Controllers\Api\V1\Finance\FinanceReceiptController;
use App\Models\Finance\FinanceExchangeRate;
use App\Models\Institution\FeeStructure;
use App\Models\Institution\ModeOfStudy;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\FeeType;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

function actingAsFinanceStaffForLedgerTests(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo('root:manage');
    test()->actingAs($user);
    Auth::setUser($user);

    return $user;
}

function createStatementForLedgerTests(array $attributes): ZBBankStatement
{
    static $sequence = 0;
    $sequence++;

    return ZBBankStatement::query()->create(array_merge([
        'tran_number_asc' => 'LA-'.$sequence,
        'tran_number_desc' => 'LD-'.$sequence,
        'transaction_id' => 'LTXN-'.$sequence,
        'transaction_sr_id' => 'LSR-'.$sequence,
        'transaction_date' => '2026-03-30T12:00:00',
        'iso_currency_code' => 'USD',
    ], $attributes));
}

function tuitionFeeTypeForLedgerTests(): FeeType
{
    return FeeType::query()->firstOrCreate(
        ['slug' => FeeTypeEnum::TUITION_FEE->slug()],
        [
            'name' => FeeTypeEnum::TUITION_FEE->name(),
            'description' => FeeTypeEnum::TUITION_FEE->description(),
            'position' => FeeTypeEnum::TUITION_FEE->position(),
        ],
    );
}

function otherFeeTypeForLedgerTests(): FeeType
{
    return FeeType::query()->firstOrCreate(
        ['slug' => FeeTypeEnum::APPLICATION_FEE->slug()],
        [
            'name' => FeeTypeEnum::APPLICATION_FEE->name(),
            'description' => FeeTypeEnum::APPLICATION_FEE->description(),
            'position' => FeeTypeEnum::APPLICATION_FEE->position(),
        ],
    );
}

function seedTuitionFeeStructureForApplication(StudentApplication $application, float $amount): FeeType
{
    $feeType = tuitionFeeTypeForLedgerTests();
    $levelId = $application->departmentLevel?->level_id
        ?? $application->departmentLevel?->level?->id;

    FeeStructure::query()->updateOrCreate(
        [
            'tenant_id' => $application->tenant_id,
            'fee_type_id' => $feeType->id,
            'level_id' => $levelId,
            'mode_of_study_id' => $application->mode_of_study_id,
        ],
        [
            'amount' => $amount,
            'local_fca_amount' => $amount,
        ],
    );

    return $feeType;
}

function setApplicationModeOfStudy(StudentApplication $application, string $modeName): void
{
    $mode = ModeOfStudy::query()->firstOrCreate(['name' => $modeName]);
    $application->update(['mode_of_study_id' => $mode->id]);
    $application->unsetRelation('modeOfStudy');
    $application->load('modeOfStudy');
}

it('returns ledger entries with summary totals and running balances', function () {
    $studentNumber = '26ICT0703086HP';

    FinanceExchangeRate::query()->create([
        'date' => '2026-03-30',
        'currency_from' => 'USD',
        'currency_to' => 'ZWG',
        'rate' => '26.380300',
    ]);

    $charge = createStatementForLedgerTests([
        'debit_credit_flag' => 'D',
        'amount_debit' => '5000.00',
        'narration' => 'Tuition '.$studentNumber,
    ]);
    $payment = createStatementForLedgerTests([
        'debit_credit_flag' => 'C',
        'amount_credit' => '1500.00',
        'narration' => 'Payment '.$studentNumber,
        'transaction_date' => '2026-03-31T12:00:00',
    ]);
    createStatementForLedgerTests([
        'narration' => 'Payment STU-OTHER tuition',
    ]);

    $student = new Student([
        'student_number' => $studentNumber,
    ]);

    actingAsFinanceStaffForLedgerTests();
    $controller = app(FinanceReceiptController::class);
    $response = $controller->getStudentLedger($student)->response()->getData(true);

    expect($response['data'])->toHaveCount(2)
        ->and($response['summary'])->toMatchArray([
            'totalInvoiced' => '5000.00',
            'totalPayments' => '1500.00',
            'outstandingBalance' => '3500.00',
            'paidPercent' => 30.0,
        ]);

    $chargeRow = collect($response['data'])->firstWhere('id', 'bank:'.$charge->id);
    $paymentRow = collect($response['data'])->firstWhere('id', 'bank:'.$payment->id);
    expect($chargeRow['attributes']['source'])->toBe('bank')
        ->and($chargeRow['attributes']['runningBalance'])->toBe('5000.00')
        ->and($paymentRow['attributes']['runningBalance'])->toBe('3500.00');
});

it('still returns only credit receipts on receipts endpoint', function () {
    $studentNumber = 'STU-RECEIPT-01';

    $credit = createStatementForLedgerTests([
        'narration' => 'Payment '.$studentNumber,
        'amount_credit' => '100.00',
        'debit_credit_flag' => 'C',
    ]);
    createStatementForLedgerTests([
        'debit_credit_flag' => 'D',
        'narration' => 'Charge '.$studentNumber,
        'amount_debit' => '500.00',
    ]);

    $student = new Student(['student_number' => $studentNumber]);
    actingAsFinanceStaffForLedgerTests();
    $controller = app(FinanceReceiptController::class);
    $data = $controller->getStudentReceipts($student)->toArray(Request::create('/', 'GET'));

    expect($data)->toHaveCount(1)
        ->and($data[0]['id'])->toBe($credit->id);
});

it('does not leak ledger entries from other students sharing a numeric prefix stem', function () {
    $studentNumber = '26ICT07022184HP';

    $charge = createStatementForLedgerTests([
        'debit_credit_flag' => 'D',
        'amount_debit' => '1000.00',
        'narration' => 'Charge '.$studentNumber,
        'transaction_date' => '2026-04-10T12:00:00',
    ]);

    $payment = createStatementForLedgerTests([
        'debit_credit_flag' => 'C',
        'amount_credit' => '200.00',
        'narration' => 'Payment '.$studentNumber,
        'transaction_date' => '2026-04-11T12:00:00',
    ]);

    createStatementForLedgerTests([
        'debit_credit_flag' => 'C',
        'amount_credit' => '50.00',
        'narration' => 'Payment 26ICT07022189HP',
    ]);

    createStatementForLedgerTests([
        'debit_credit_flag' => 'D',
        'amount_debit' => '75.00',
        'narration' => 'Charge 26ICT07022180HP',
    ]);

    $student = new Student(['student_number' => $studentNumber]);
    actingAsFinanceStaffForLedgerTests();
    $controller = app(FinanceReceiptController::class);
    $response = $controller->getStudentLedger($student)->response()->getData(true);
    $entryIds = collect($response['data'])->pluck('id')->all();

    expect($entryIds)->toEqualCanonicalizing(['bank:'.$charge->id, 'bank:'.$payment->id])
        ->and($response['summary'])->toMatchArray([
            'totalInvoiced' => '1000.00',
            'totalPayments' => '200.00',
            'outstandingBalance' => '800.00',
            'paidPercent' => 20.0,
        ]);
});

it('uses fee-structure tuition and part-time levy as assessed invoices with bank and online payments', function () {
    $studentApplication = createVerifiedStudentApplication('MERGE-LEDGER-01');
    setApplicationModeOfStudy($studentApplication, 'Part Time');
    $feeType = seedTuitionFeeStructureForApplication($studentApplication, 330);
    $student = $studentApplication->student->fresh();

    $bankPayment = createStatementForLedgerTests([
        'debit_credit_flag' => 'C',
        'amount_credit' => '360.00',
        'narration' => 'NC & PT LEVY '.$student->student_number,
        'transaction_date' => '2026-01-20T10:00:00',
    ]);

    $onlineReceipt = Ledger::query()->create([
        'tenant_id' => $studentApplication->tenant_id,
        'ledgerable_type' => StudentApplication::class,
        'ledgerable_id' => $studentApplication->id,
        'student_application_id' => $studentApplication->id,
        'fee_type_id' => $feeType->id,
        'type' => 'receipt',
        'payment_status' => 'paid',
        'amount' => 5,
        'currency' => 'USD',
        'system_reference' => 'ORD-MERGE-001',
        'payment_date' => '2026-01-21 09:00:00',
        'intake_period_id' => $studentApplication->intake_period_id,
    ]);

    Ledger::query()->create([
        'tenant_id' => $studentApplication->tenant_id,
        'ledgerable_type' => StudentApplication::class,
        'ledgerable_id' => $studentApplication->id,
        'student_application_id' => $studentApplication->id,
        'fee_type_id' => $feeType->id,
        'type' => 'invoice',
        'payment_status' => 'pending',
        'amount' => 5,
        'currency' => 'USD',
        'system_reference' => 'INV-MERGE-IGNORE',
        'due_date' => '2026-01-21 08:00:00',
        'intake_period_id' => $studentApplication->intake_period_id,
    ]);

    actingAsFinanceStaffForLedgerTests();
    $response = app(FinanceReceiptController::class)
        ->getStudentLedger($student)
        ->response()
        ->getData(true);

    $ids = collect($response['data'])->pluck('id')->all();

    expect($ids)->toEqualCanonicalizing([
        'assessed:tuition',
        'assessed:part_time_levy',
        'bank:'.$bankPayment->id,
        'ledger:'.$onlineReceipt->id,
    ])
        ->and($response['summary'])->toMatchArray([
            'totalInvoiced' => '365.00',
            'totalPayments' => '365.00',
            'outstandingBalance' => '0.00',
            'paidPercent' => 100.0,
        ]);

    $tuitionRow = collect($response['data'])->firstWhere('id', 'assessed:tuition');
    $levyRow = collect($response['data'])->firstWhere('id', 'assessed:part_time_levy');
    $onlineRow = collect($response['data'])->firstWhere('id', 'ledger:'.$onlineReceipt->id);
    $lastBalance = collect($response['data'])->last()['attributes']['runningBalance'];

    expect($tuitionRow['attributes']['source'])->toBe('assessed')
        ->and($tuitionRow['attributes']['amountDebit'])->toBe('330.00')
        ->and($levyRow['attributes']['amountDebit'])->toBe('35.00')
        ->and($onlineRow['attributes']['source'])->toBe('online')
        ->and($onlineRow['attributes']['amountCredit'])->toBe('5.00')
        ->and($lastBalance)->toBe('0.00');
});

it('excludes tuition ledger invoices and non-tuition ledgers while keeping assessed fee-structure invoices', function () {
    $studentApplication = createVerifiedStudentApplication('MERGE-LEDGER-02');
    setApplicationModeOfStudy($studentApplication, 'Full Time');
    $tuitionFeeType = seedTuitionFeeStructureForApplication($studentApplication, 100);
    $otherFeeType = otherFeeTypeForLedgerTests();
    $student = $studentApplication->student->fresh();

    $ledgerInvoice = Ledger::query()->create([
        'tenant_id' => $studentApplication->tenant_id,
        'ledgerable_type' => StudentApplication::class,
        'ledgerable_id' => $studentApplication->id,
        'student_application_id' => $studentApplication->id,
        'fee_type_id' => $tuitionFeeType->id,
        'type' => 'invoice',
        'payment_status' => 'pending',
        'amount' => 50,
        'currency' => 'USD',
        'system_reference' => 'INV-MERGE-001',
        'due_date' => '2026-02-01 08:00:00',
        'intake_period_id' => $studentApplication->intake_period_id,
    ]);

    $receipt = Ledger::query()->create([
        'tenant_id' => $studentApplication->tenant_id,
        'ledgerable_type' => StudentApplication::class,
        'ledgerable_id' => $studentApplication->id,
        'student_application_id' => $studentApplication->id,
        'fee_type_id' => $tuitionFeeType->id,
        'type' => 'receipt',
        'payment_status' => 'paid',
        'amount' => 40,
        'currency' => 'USD',
        'system_reference' => 'ORD-MERGE-002',
        'payment_date' => '2026-02-02 10:00:00',
        'intake_period_id' => $studentApplication->intake_period_id,
    ]);

    Ledger::query()->create([
        'tenant_id' => $studentApplication->tenant_id,
        'ledgerable_type' => StudentApplication::class,
        'ledgerable_id' => $studentApplication->id,
        'student_application_id' => $studentApplication->id,
        'fee_type_id' => $otherFeeType->id,
        'type' => 'receipt',
        'payment_status' => 'paid',
        'amount' => 25,
        'currency' => 'USD',
        'system_reference' => 'ORD-APP-FEE',
        'payment_date' => '2026-02-03 10:00:00',
        'intake_period_id' => $studentApplication->intake_period_id,
    ]);

    actingAsFinanceStaffForLedgerTests();
    $response = app(FinanceReceiptController::class)
        ->getStudentLedger($student)
        ->response()
        ->getData(true);

    $ids = collect($response['data'])->pluck('id')->all();

    expect($ids)->toEqualCanonicalizing(['assessed:tuition', 'ledger:'.$receipt->id])
        ->and($ids)->not->toContain('ledger:'.$ledgerInvoice->id)
        ->and($ids)->not->toContain('assessed:part_time_levy')
        ->and($response['summary'])->toMatchArray([
            'totalInvoiced' => '100.00',
            'totalPayments' => '40.00',
            'outstandingBalance' => '60.00',
            'paidPercent' => 40.0,
        ]);

    $assessedRow = collect($response['data'])->firstWhere('id', 'assessed:tuition');
    expect($assessedRow['attributes']['source'])->toBe('assessed')
        ->and($assessedRow['attributes']['amountDebit'])->toBe('100.00');
});
