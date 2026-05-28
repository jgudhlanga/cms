<?php

use App\Http\Controllers\Api\V1\Finance\FinanceReceiptController;
use App\Models\Finance\FinanceExchangeRate;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Students\Student;
use Illuminate\Http\Request;

it('returns only credit receipts matching student number across supported fields', function () {
    $studentNumber = 'STU-12345';

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
            'tran_number_asc' => 'TA-'.$sequence,
            'tran_number_desc' => 'TD-'.$sequence,
            'transaction_id' => 'TXN-'.$sequence,
            'transaction_sr_id' => 'SR-'.$sequence,
            'transaction_date' => '2026-03-30T12:00:00',
            'debit_credit_flag' => 'C',
        ], $attributes));
    };

    $narrationMatch = $createStatement([
        'code' => 'C-1',
        'amount_credit' => '150.25',
        'iso_currency_code' => 'USD',
        'pipe1_details' => 'Pipe one details',
        'narration' => 'Payment '.$studentNumber.' tuition',
    ]);
    $pipe5Match = $createStatement([
        'pipe5_details' => 'Ref '.$studentNumber.' hostels',
        'amount_credit' => '26380.30',
        'iso_currency_code' => 'ZWG',
    ]);
    $pipe10Match = $createStatement([
        'pipe10_details' => 'Invoice '.$studentNumber.' exam',
    ]);
    $transactionDetailsMatch = $createStatement([
        'transaction_details' => 'Bank transfer '.$studentNumber.' fees',
    ]);
    $createStatement([
        'debit_credit_flag' => 'D',
        'transaction_details' => 'Bank transfer '.$studentNumber.' fees',
    ]);
    $createStatement([
        'narration' => 'Payment STU-99999 tuition',
    ]);

    $student = new Student([
        'student_number' => $studentNumber,
    ]);

    $controller = new FinanceReceiptController;
    $resourceCollection = $controller->getStudentReceipts($student);
    $data = $resourceCollection->toArray(Request::create('/', 'GET'));

    expect($data)->toHaveCount(4);

    $receiptIds = collect($data)->pluck('id');

    expect($receiptIds->all())->toEqualCanonicalizing([
        $narrationMatch->id,
        $pipe5Match->id,
        $pipe10Match->id,
        $transactionDetailsMatch->id,
    ]);

    $narrationReceipt = collect($data)->firstWhere('id', $narrationMatch->id);

    expect($narrationReceipt)->toMatchArray([
        'id' => $narrationMatch->id,
    ]);

    expect($narrationReceipt['attributes'])->toMatchArray([
        'tranNumberAsc' => $narrationMatch->tran_number_asc,
        'tranNumberDesc' => $narrationMatch->tran_number_desc,
        'transactionId' => $narrationMatch->transaction_id,
        'transactionSrId' => $narrationMatch->transaction_sr_id,
        'debitCreditFlag' => $narrationMatch->debit_credit_flag,
        'amountCredit' => $narrationMatch->amount_credit,
        'isoCurrencyCode' => $narrationMatch->iso_currency_code,
        'usdConversionRate' => null,
        'usdConversionRateLabel' => null,
        'usdConversionRateDate' => null,
        'originalAmountCredit' => null,
        'originalAmountDebit' => null,
        'originalIsoCurrencyCode' => null,
        'pipe1Details' => $narrationMatch->pipe1_details,
    ]);

    $pipe5Receipt = collect($data)->firstWhere('id', $pipe5Match->id);

    expect($pipe5Receipt['attributes'])->toMatchArray([
        'amountCredit' => '1000.00',
        'isoCurrencyCode' => 'USD',
        'usdConversionRate' => '26.380300',
        'usdConversionRateLabel' => 'ZWG/USD @ 26.380300',
        'usdConversionRateDate' => '2026-03-30',
        'originalAmountCredit' => '26380.30',
        'originalAmountDebit' => null,
        'originalIsoCurrencyCode' => 'ZWG',
    ]);

    expect(array_key_exists('transactionDate', $narrationReceipt['attributes']))->toBeTrue();
    expect(array_key_exists('transactionDetails', $narrationReceipt['attributes']))->toBeTrue();
    expect(array_key_exists('createdAt', $narrationReceipt['attributes']))->toBeTrue();
    expect(array_key_exists('updatedAt', $narrationReceipt['attributes']))->toBeTrue();
    expect(array_key_exists('deletedAt', $narrationReceipt['attributes']))->toBeTrue();
});

it('strictly matches receipts by exact student number', function () {
    $studentNumber = '26ICT0703086HP';

    $createStatement = function (array $attributes): ZBBankStatement {
        static $sequence = 0;
        $sequence++;

        return ZBBankStatement::query()->create(array_merge([
            'tran_number_asc' => 'TTA-'.$sequence,
            'tran_number_desc' => 'TTD-'.$sequence,
            'transaction_id' => 'TTXN-'.$sequence,
            'transaction_sr_id' => 'TSR-'.$sequence,
            'transaction_date' => '2026-04-09T12:00:00',
            'debit_credit_flag' => 'C',
            'iso_currency_code' => 'USD',
        ], $attributes));
    };

    $fullMatch = $createStatement([
        'narration' => 'Payment '.$studentNumber.' tuition',
        'amount_credit' => '100.00',
    ]);

    $createStatement([
        'narration' => 'Payment 26ICT0703999HP tuition',
        'amount_credit' => '80.00',
    ]);

    $student = new Student(['student_number' => $studentNumber]);
    $controller = new FinanceReceiptController;
    $data = $controller->getStudentReceipts($student)->toArray(Request::create('/', 'GET'));
    $receiptIds = collect($data)->pluck('id')->all();

    expect($receiptIds)->toEqualCanonicalizing([$fullMatch->id]);
});

it('does not leak receipts from other students sharing a numeric prefix stem', function () {
    $studentNumber = '26ICT07022184HP';

    $createStatement = function (array $attributes): ZBBankStatement {
        static $sequence = 0;
        $sequence++;

        return ZBBankStatement::query()->create(array_merge([
            'tran_number_asc' => 'COLA-'.$sequence,
            'tran_number_desc' => 'COLD-'.$sequence,
            'transaction_id' => 'COLTXN-'.$sequence,
            'transaction_sr_id' => 'COLSR-'.$sequence,
            'transaction_date' => '2026-04-10T12:00:00',
            'debit_credit_flag' => 'C',
            'iso_currency_code' => 'USD',
        ], $attributes));
    };

    $fullMatch = $createStatement([
        'narration' => 'Payment '.$studentNumber.' tuition',
        'amount_credit' => '120.00',
    ]);

    $createStatement([
        'narration' => 'Payment 26ICT07022189HP tuition',
        'amount_credit' => '70.00',
    ]);

    $createStatement([
        'narration' => 'Payment 26ICT07022180HP tuition',
        'amount_credit' => '80.00',
    ]);

    $student = new Student(['student_number' => $studentNumber]);
    $controller = new FinanceReceiptController;
    $data = $controller->getStudentReceipts($student)->toArray(Request::create('/', 'GET'));
    $receiptIds = collect($data)->pluck('id')->all();

    expect($receiptIds)->toEqualCanonicalizing([$fullMatch->id]);
});
