<?php

use App\Models\Integrations\Banks\BankPayment;
use Illuminate\Support\Facades\Http;

it('processes payments with the renamed command arguments', function () {
    config()->set('custom.payments.bank_payments_base_url', 'https://bank.example');
    config()->set('custom.payments.usd.institution_id', 'USD123');
    config()->set('custom.payments.usd.password', 'secret');

    Http::fake([
        'https://bank.example/alerts/payments/all-payments' => Http::response([
            [
                'transaction_id' => 'TXN-001',
                'amount' => 24.50,
                'date' => now()->toDateTimeString(),
                'status' => 'pending',
            ],
        ], 200),
    ]);

    $this->artisan('app:get-payments-command', [
        'accountType' => 'usd',
        'transactionType' => 'all',
    ])->expectsOutput('1. Processing payment: TXN-001')
        ->expectsOutput('Totals - Processed: 1, Successful: 1, Failed: 0.')
        ->expectsOutput('Request completed successfully.')
        ->assertSuccessful();

    expect(BankPayment::query()->where('transaction_id', 'TXN-001')->exists())->toBeTrue();
});

it('defaults invalid accountType to usd', function () {
    config()->set('custom.payments.bank_payments_base_url', 'https://bank.example');
    config()->set('custom.payments.usd.institution_id', 'USD123');
    config()->set('custom.payments.usd.password', 'secret');

    Http::fake([
        'https://bank.example/alerts/payments/pick-all-pending' => Http::response([
            [
                'transaction_id' => 'TXN-002',
                'amount' => 10,
                'date' => now()->toDateTimeString(),
                'status' => 'pending',
            ],
        ], 200),
    ]);

    $this->artisan('app:get-payments-command', [
        'accountType' => 'not-valid',
        'transactionType' => 'pending',
    ])->expectsOutput("Invalid accountType 'not-valid'. Defaulting to 'usd'. Allowed: usd, zwg, income-gen.")
        ->assertSuccessful();
});

it('defaults invalid transactionType to pending', function () {
    config()->set('custom.payments.bank_payments_base_url', 'https://bank.example');
    config()->set('custom.payments.usd.institution_id', 'USD123');
    config()->set('custom.payments.usd.password', 'secret');

    Http::fake([
        'https://bank.example/alerts/payments/pick-all-pending' => Http::response([
            [
                'transaction_id' => 'TXN-003',
                'amount' => 11,
                'date' => now()->toDateTimeString(),
                'status' => 'pending',
            ],
        ], 200),
    ]);

    $this->artisan('app:get-payments-command', [
        'accountType' => 'usd',
        'transactionType' => 'mismatch',
    ])->expectsOutput("Invalid transactionType 'mismatch'. Defaulting to 'pending'. Allowed: all, pending.")
        ->assertSuccessful();
});

it('tracks failed records when transaction id is missing', function () {
    config()->set('custom.payments.bank_payments_base_url', 'https://bank.example');
    config()->set('custom.payments.usd.institution_id', 'USD123');
    config()->set('custom.payments.usd.password', 'secret');

    Http::fake([
        'https://bank.example/alerts/payments/all-payments' => Http::response([
            [
                'transaction_id' => 'TXN-VALID',
                'amount' => 99.99,
                'date' => now()->toDateTimeString(),
                'status' => 'pending',
            ],
            [
                'amount' => 18.50,
                'date' => now()->toDateTimeString(),
                'status' => 'pending',
            ],
        ], 200),
    ]);

    $this->artisan('app:get-payments-command', [
        'accountType' => 'usd',
        'transactionType' => 'all',
    ])->expectsOutput('1. Processing payment: TXN-VALID')
        ->expectsOutput('Skipping payment with no transaction_id/id.')
        ->expectsOutput('Totals - Processed: 2, Successful: 1, Failed: 1.')
        ->expectsOutput('Request completed successfully.')
        ->assertSuccessful();
});

it('retries transient connection failures using configured retry values', function () {
    config()->set('custom.payments.bank_payments_base_url', 'https://bank.example');
    config()->set('custom.payments.bank_payments_connect_timeout', 3);
    config()->set('custom.payments.bank_payments_timeout', 15);
    config()->set('custom.payments.bank_payments_retry_times', 2);
    config()->set('custom.payments.bank_payments_retry_sleep_ms', 0);
    config()->set('custom.payments.usd.institution_id', 'USD123');
    config()->set('custom.payments.usd.password', 'secret');

    $attempt = 0;

    Http::fake(function () use (&$attempt) {
        $attempt++;

        if ($attempt === 1) {
            return Http::failedConnection();
        }

        return Http::response([
            [
                'transaction_id' => 'TXN-RETRY',
                'amount' => 55.30,
                'date' => now()->toDateTimeString(),
                'status' => 'pending',
            ],
        ], 200);
    });

    $this->artisan('app:get-payments-command', [
        'accountType' => 'usd',
        'transactionType' => 'all',
    ])->expectsOutput('1. Processing payment: TXN-RETRY')
        ->expectsOutput('Totals - Processed: 1, Successful: 1, Failed: 0.')
        ->assertSuccessful();

    Http::assertSentCount(2);
});

it('shows timeout and retry settings when connection fails', function () {
    config()->set('custom.payments.bank_payments_base_url', 'https://bank.example');
    config()->set('custom.payments.bank_payments_connect_timeout', 5);
    config()->set('custom.payments.bank_payments_timeout', 25);
    config()->set('custom.payments.bank_payments_retry_times', 2);
    config()->set('custom.payments.bank_payments_retry_sleep_ms', 100);
    config()->set('custom.payments.usd.institution_id', 'USD123');
    config()->set('custom.payments.usd.password', 'secret');

    Http::fake([
        'https://bank.example/alerts/payments/all-payments' => Http::failedConnection(),
    ]);

    $this->artisan('app:get-payments-command', [
        'accountType' => 'usd',
        'transactionType' => 'all',
    ])->expectsOutputToContain('Connection error calling bank payments API:')
        ->expectsOutput('HTTP client settings: connect_timeout=5s timeout=25s retries=2 retry_sleep_ms=100.')
        ->assertFailed();
});
