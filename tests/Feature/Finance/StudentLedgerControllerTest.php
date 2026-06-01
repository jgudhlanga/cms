<?php

use App\Http\Controllers\Api\V1\Finance\FinanceReceiptController;
use App\Models\Finance\FinanceExchangeRate;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Students\Student;
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

it('returns ledger entries with summary totals and running balances', function () {
    $studentNumber = '26ICT0703086HP';

    FinanceExchangeRate::query()->create([
        'date' => '2026-03-30',
        'currency_from' => 'USD',
        'currency_to' => 'ZWG',
        'rate' => '26.380300',
    ]);

    $createStatement = function (array $attributes): ZBBankStatement {
        static $sequence = 0;
        $sequence++;

        return ZBBankStatement::query()->create(array_merge([
            'tran_number_asc' => 'LA-'.$sequence,
            'tran_number_desc' => 'LD-'.$sequence,
            'transaction_id' => 'LTXN-'.$sequence,
            'transaction_sr_id' => 'LSR-'.$sequence,
            'transaction_date' => '2026-03-30T12:00:00',
        ], $attributes));
    };

    $charge = $createStatement([
        'debit_credit_flag' => 'D',
        'amount_debit' => '5000.00',
        'iso_currency_code' => 'USD',
        'narration' => 'Tuition '.$studentNumber,
    ]);
    $payment = $createStatement([
        'debit_credit_flag' => 'C',
        'amount_credit' => '1500.00',
        'iso_currency_code' => 'USD',
        'narration' => 'Payment '.$studentNumber,
        'transaction_date' => '2026-03-31T12:00:00',
    ]);
    $createStatement([
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

    $chargeRow = collect($response['data'])->firstWhere('id', $charge->id);
    $paymentRow = collect($response['data'])->firstWhere('id', $payment->id);
    expect($chargeRow['attributes']['runningBalance'])->toBe('5000.00')
        ->and($paymentRow['attributes']['runningBalance'])->toBe('3500.00');
});

it('still returns only credit receipts on receipts endpoint', function () {
    $studentNumber = 'STU-RECEIPT-01';

    $createStatement = function (array $attributes): ZBBankStatement {
        static $sequence = 0;
        $sequence++;

        return ZBBankStatement::query()->create(array_merge([
            'tran_number_asc' => 'RA-'.$sequence,
            'tran_number_desc' => 'RD-'.$sequence,
            'transaction_id' => 'RTXN-'.$sequence,
            'transaction_sr_id' => 'RSR-'.$sequence,
            'transaction_date' => '2026-03-30T12:00:00',
            'debit_credit_flag' => 'C',
        ], $attributes));
    };

    $credit = $createStatement([
        'narration' => 'Payment '.$studentNumber,
        'amount_credit' => '100.00',
        'iso_currency_code' => 'USD',
    ]);
    $createStatement([
        'debit_credit_flag' => 'D',
        'narration' => 'Charge '.$studentNumber,
        'amount_debit' => '500.00',
        'iso_currency_code' => 'USD',
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

    $createStatement = function (array $attributes): ZBBankStatement {
        static $sequence = 0;
        $sequence++;

        return ZBBankStatement::query()->create(array_merge([
            'tran_number_asc' => 'LSA-'.$sequence,
            'tran_number_desc' => 'LSD-'.$sequence,
            'transaction_id' => 'LSTXN-'.$sequence,
            'transaction_sr_id' => 'LSSR-'.$sequence,
            'transaction_date' => '2026-04-10T12:00:00',
            'iso_currency_code' => 'USD',
        ], $attributes));
    };

    $charge = $createStatement([
        'debit_credit_flag' => 'D',
        'amount_debit' => '1000.00',
        'narration' => 'Charge '.$studentNumber,
    ]);

    $payment = $createStatement([
        'debit_credit_flag' => 'C',
        'amount_credit' => '200.00',
        'narration' => 'Payment '.$studentNumber,
        'transaction_date' => '2026-04-11T12:00:00',
    ]);

    $createStatement([
        'debit_credit_flag' => 'C',
        'amount_credit' => '50.00',
        'narration' => 'Payment 26ICT07022189HP',
    ]);

    $createStatement([
        'debit_credit_flag' => 'D',
        'amount_debit' => '75.00',
        'narration' => 'Charge 26ICT07022180HP',
    ]);

    $student = new Student(['student_number' => $studentNumber]);
    actingAsFinanceStaffForLedgerTests();
    $controller = app(FinanceReceiptController::class);
    $response = $controller->getStudentLedger($student)->response()->getData(true);
    $entryIds = collect($response['data'])->pluck('id')->all();

    expect($entryIds)->toEqualCanonicalizing([$charge->id, $payment->id])
        ->and($response['summary'])->toMatchArray([
            'totalInvoiced' => '1000.00',
            'totalPayments' => '200.00',
            'outstandingBalance' => '800.00',
            'paidPercent' => 20.0,
        ]);
});
