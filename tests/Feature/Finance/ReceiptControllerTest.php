<?php

use App\Http\Controllers\Api\V1\Finance\FinanceReceiptController;
use App\Models\Integrations\Banks\BankPayment;
use App\Models\Students\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

it('returns student receipt dates formatted as d-m-y H:i:s', function () {
    $studentNumber = 'STU-0001';

    $transactionCreatedAt = Carbon::create(2026, 3, 25, 14, 30, 5, 'Africa/Harare');
    $transactionDate = Carbon::create(2026, 3, 25, 16, 45, 10, 'Africa/Harare');

    BankPayment::query()->create([
        'transaction_id' => 'TXN-100',
        'bank' => 'zb',
        'amount' => 24.50,

        'transaction_created_date' => $transactionCreatedAt,
        'transaction_date' => $transactionDate,

        'nr1' => null,
        'nr2' => null,
        'nr3' => $studentNumber,
        'nr4' => null,

        'picked' => null,
        'reference' => null,
        'source' => null,
        'status' => 'pending',
        'tcd' => null,
        'narrative' => null,
    ]);

    $payment = BankPayment::query()
        ->where('transaction_id', 'TXN-100')
        ->firstOrFail();

    $expectedTransactionCreatedDate = Carbon::parse((string) $payment->transaction_created_date)->format('d-m-y H:i:s');
    $expectedTransactionDate = $payment->transaction_date === null
        ? null
        : Carbon::parse((string) $payment->transaction_date)->format('d-m-y H:i:s');

    $expectedCreatedAt = $payment->created_at->format('d-m-y H:i:s');
    $expectedUpdatedAt = $payment->updated_at->format('d-m-y H:i:s');

    $student = new Student([
        'student_number' => $studentNumber,
    ]);

    $controller = new FinanceReceiptController;
    $resourceCollection = $controller->getStudentReceipts($student);
    $data = $resourceCollection->toArray(Request::create('/', 'GET'));

    expect($data)->toHaveCount(1);

    $item = $data[0];
    expect($item['transaction_created_date'])->toBe($expectedTransactionCreatedDate);
    expect($item['transaction_date'])->toBe($expectedTransactionDate);
    expect($item['created_at'])->toBe($expectedCreatedAt);
    expect($item['updated_at'])->toBe($expectedUpdatedAt);
    expect($item['deleted_at'])->toBeNull();
});
