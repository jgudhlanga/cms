<?php

use App\Jobs\Integrations\Banks\ZB\FetchBankPaymentsJob;
use Illuminate\Support\Facades\Queue;

it('dispatches all bank payment fetch combinations to the payments queue', function () {
    config()->set('custom.payments.bank_payments_queue', 'payments');
    Queue::fake();

    $this->artisan('payments:dispatch')
        ->assertSuccessful();

    $expected = [
        ['usd', 'all'],
        ['usd', 'pending'],
        ['zwg', 'all'],
        ['zwg', 'pending'],
        ['income-gen', 'all'],
        ['income-gen', 'pending'],
    ];

    Queue::assertPushed(FetchBankPaymentsJob::class, 6);

    foreach ($expected as [$accountType, $transactionType]) {
        Queue::assertPushed(FetchBankPaymentsJob::class, function (FetchBankPaymentsJob $job) use ($accountType, $transactionType): bool {
            return $job->accountType === $accountType
                && $job->transactionType === $transactionType
                && $job->queue === 'payments';
        });
    }
});
