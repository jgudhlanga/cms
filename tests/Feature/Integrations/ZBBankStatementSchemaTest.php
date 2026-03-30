<?php

use App\Enums\Integrations\Banks\ZBBankStatementFetchWindowStatus;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Integrations\Banks\ZBBankStatementFetchWindow;
use App\Services\Integrations\Banks\ZB\FetchBankStatementService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

it('creates zb bank statements table with expected columns', function () {
    expect(Schema::hasTable('zb_bank_statements'))->toBeTrue();

    expect(Schema::hasColumns('zb_bank_statements', [
        'id',
        'tran_number_asc',
        'tran_number_desc',
        'transaction_id',
        'transaction_sr_id',
        'transaction_date',
        'narration',
        'reference',
        'code',
        'description',
        'debit_credit_flag',
        'amount_credit',
        'amount_debit',
        'cleared_running_balance',
        'blocked_balance',
        'debit_limit',
        'credit_limit',
        'iso_currency_code',
        'account_description',
        'ubfull_name',
        'pipe_count',
        'pipe1',
        'pipe2',
        'pipe3',
        'pipe4',
        'pipe5',
        'pipe6',
        'pipe7',
        'pipe8',
        'pipe9',
        'pipe10',
        'pipe1_details',
        'pipe2_details',
        'pipe3_details',
        'pipe4_details',
        'pipe5_details',
        'pipe6_details',
        'pipe7_details',
        'pipe8_details',
        'pipe9_details',
        'pipe10_details',
        'transaction_details',
        'created_at',
        'updated_at',
        'deleted_at',
    ]))->toBeTrue();
});

it('creates zb bank statement fetch windows table with expected columns', function () {
    expect(Schema::hasTable('zb_bank_statement_fetch_windows'))->toBeTrue();

    expect(Schema::hasColumns('zb_bank_statement_fetch_windows', [
        'id',
        'account_type',
        'window_start',
        'window_end',
        'status',
        'attempt_count',
        'processing_started_at',
        'succeeded_at',
        'failed_at',
        'last_error',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

it('enforces unique account type and window bounds on fetch windows', function () {
    ZBBankStatementFetchWindow::query()->create([
        'account_type' => 'usd',
        'window_start' => '2026-01-01',
        'window_end' => '2026-01-07',
        'status' => ZBBankStatementFetchWindowStatus::Pending,
        'attempt_count' => 0,
    ]);

    expect(fn () => ZBBankStatementFetchWindow::query()->create([
        'account_type' => 'usd',
        'window_start' => '2026-01-01',
        'window_end' => '2026-01-07',
        'status' => ZBBankStatementFetchWindowStatus::Pending,
        'attempt_count' => 0,
    ]))->toThrow(QueryException::class);
});

it('enforces unique transaction id', function () {
    $payload = [
        'tran_number_asc' => '14637',
        'tran_number_desc' => '1',
        'transaction_id' => '0419d2e8fe59dy0A',
        'transaction_sr_id' => '0419d2e8fe59ey0F',
        'transaction_date' => '2026-03-27 11:11:15.797',
    ];

    ZBBankStatement::query()->create($payload);

    expect(fn () => ZBBankStatement::query()->create($payload))
        ->toThrow(QueryException::class);
});

it('maps and upserts statement transactions into zb bank statements', function () {
    config()->set('custom.bank-statements.base_url', 'https://bank.example/');
    config()->set('custom.bank-statements.usd.account_number', 'USD123');
    config()->set('custom.bank-statements.usd.password', 'secret');

    Http::fake([
        'https://bank.example/v1/statements' => Http::sequence()
            ->push([
                'transactions' => [
                    [
                        'tranNumberAsc' => '1',
                        'tranNumberDesc' => '14637',
                        'transactionId' => 'stmt-001',
                        'transactionSRId' => 'sr-001',
                        'transactionDate' => '2026-01-01 03:20:20.15',
                        'narration' => 'Original narration',
                        'reference' => 'REF-001',
                        'code' => 'S02',
                        'description' => 'Credit Interest Application',
                        'debitCreditFlag' => 'C',
                        'amountCredit' => '1064.750000',
                        'amountDebit' => '0.000000',
                        'clearedRunningBalance' => '2471720.940000',
                        'blockedBalance' => '0.000000',
                        'debitLimit' => '0.000000',
                        'creditLimit' => '0.000000',
                        'isoCurrencyCode' => 'USD',
                        'accountDescription' => 'Current Account',
                        'ubfullName' => 'HARARE POLYTECHNIC',
                        'pipeCount' => '2',
                        'pipe1' => '10',
                        'pipe2' => '20',
                        'pipe1Details' => 'P1',
                        'pipe2Details' => 'P2',
                        'transactionDetails' => 'Details',
                    ],
                ],
            ], 200)
            ->push([
                'transactions' => [
                    [
                        'tranNumberAsc' => '1',
                        'tranNumberDesc' => '14637',
                        'transactionId' => 'stmt-001',
                        'transactionSRId' => 'sr-001-updated',
                        'transactionDate' => '2026-01-01 03:20:20.15',
                        'narration' => 'Updated narration',
                    ],
                ],
            ], 200),
    ]);

    $service = app(FetchBankStatementService::class);
    $result = $service->execute('usd', '2026-01-01', '2026-01-31');

    expect($result)->toBe(0);

    $statement = ZBBankStatement::query()->where('transaction_id', 'stmt-001')->first();

    expect($statement)->not->toBeNull()
        ->and($statement?->tran_number_asc)->toBe('1')
        ->and($statement?->tran_number_desc)->toBe('14637')
        ->and($statement?->transaction_sr_id)->toBe('sr-001')
        ->and($statement?->debit_credit_flag)->toBe('C')
        ->and($statement?->amount_credit)->toBe('1064.750000')
        ->and($statement?->pipe1_details)->toBe('P1')
        ->and($statement?->transaction_details)->toBe('Details');

    $secondResult = $service->execute('usd', '2026-01-01', '2026-01-31');
    expect($secondResult)->toBe(0);

    $updatedStatement = ZBBankStatement::query()->where('transaction_id', 'stmt-001')->first();

    expect($updatedStatement)->not->toBeNull()
        ->and($updatedStatement?->transaction_sr_id)->toBe('sr-001-updated')
        ->and($updatedStatement?->narration)->toBe('Updated narration');
});
