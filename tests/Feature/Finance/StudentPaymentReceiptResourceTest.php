<?php

use App\Http\Resources\Finance\StudentPaymentReceiptResource;
use App\Models\Integrations\Banks\BankPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

it('formats receipt dates as d-m-y H:i:s', function () {
    config()->set('app.timezone', 'Africa/Harare');

    $transactionCreatedAt = Carbon::create(2026, 3, 25, 14, 30, 5, 'Africa/Harare');
    $transactionDate = Carbon::create(2026, 3, 25, 16, 45, 10, 'Africa/Harare');
    $createdAt = Carbon::create(2026, 3, 24, 1, 2, 3, 'Africa/Harare');
    $updatedAt = Carbon::create(2026, 3, 23, 9, 8, 7, 'Africa/Harare');

    $payment = new BankPayment([
        'id' => 1,
        'transaction_id' => 'TXN-001',
        'bank' => 'zb',
        'amount' => 24.50,

        'transaction_created_date' => $transactionCreatedAt,
        'transaction_date' => $transactionDate,
    ]);

    // Eloquent mass-assignment ignores created_at/updated_at on this model.
    $payment->forceFill([
        'created_at' => $createdAt,
        'updated_at' => $updatedAt,
        'deleted_at' => null,
    ]);

    $resource = new StudentPaymentReceiptResource($payment);
    $data = $resource->toArray(Request::create('/', 'GET'));

    expect($data['transaction_created_date'])->toBe($transactionCreatedAt->format('d-m-y H:i:s'));
    expect($data['transaction_date'])->toBe($transactionDate->format('d-m-y H:i:s'));
    expect($data['created_at'])->toBe($createdAt->format('d-m-y H:i:s'));
    expect($data['updated_at'])->toBe($updatedAt->format('d-m-y H:i:s'));
    expect($data['deleted_at'])->toBeNull();
});
