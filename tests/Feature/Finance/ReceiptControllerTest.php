<?php

use App\Http\Controllers\Api\V1\Finance\FinanceReceiptController;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Students\Student;
use Illuminate\Http\Request;

it('returns only credit receipts matching student number across supported fields', function () {
    $studentNumber = 'STU-12345';

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
        'tranNumberAsc' => $narrationMatch->tran_number_asc,
        'tranNumberDesc' => $narrationMatch->tran_number_desc,
        'transactionId' => $narrationMatch->transaction_id,
        'transactionSrId' => $narrationMatch->transaction_sr_id,
        'debitCreditFlag' => $narrationMatch->debit_credit_flag,
        'amountCredit' => $narrationMatch->amount_credit,
        'isoCurrencyCode' => $narrationMatch->iso_currency_code,
        'pipe1Details' => $narrationMatch->pipe1_details,
    ]);

    expect(array_key_exists('transactionDate', $narrationReceipt))->toBeTrue();
    expect(array_key_exists('transactionDetails', $narrationReceipt))->toBeTrue();
    expect(array_key_exists('createdAt', $narrationReceipt))->toBeTrue();
    expect(array_key_exists('updatedAt', $narrationReceipt))->toBeTrue();
    expect(array_key_exists('deletedAt', $narrationReceipt))->toBeTrue();
});
