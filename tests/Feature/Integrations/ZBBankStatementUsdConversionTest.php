<?php

use App\Models\Finance\FinanceExchangeRate;
use App\Models\Integrations\Banks\ZBBankStatement;

function makeZbStatement(array $attributes = []): ZBBankStatement
{
    static $sequence = 0;
    $sequence++;

    return ZBBankStatement::query()->create(array_merge([
        'tran_number_asc' => 'TA-'.$sequence,
        'tran_number_desc' => 'TD-'.$sequence,
        'transaction_id' => 'TXN-'.$sequence,
        'transaction_sr_id' => 'SR-'.$sequence,
        'transaction_date' => '2026-03-25T12:00:00',
        'iso_currency_code' => 'ZWG',
    ], $attributes));
}

it('converts zwg amounts to usd using usd to zwg rates by dividing', function () {
    FinanceExchangeRate::query()->create([
        'date' => '2026-03-25',
        'currency_from' => 'USD',
        'currency_to' => 'ZWG',
        'rate' => '26.380300',
    ]);

    $statement = makeZbStatement([
        'amount_credit' => '26380.30',
    ]);

    expect($statement->amountCreditInUsd())->toBe('1000.00');
});

it('converts zwg amounts to usd by dividing even when using zwg to usd pair', function () {
    FinanceExchangeRate::query()->create([
        'date' => '2026-03-25',
        'currency_from' => 'ZWG',
        'currency_to' => 'USD',
        'rate' => '26.380300',
    ]);

    $statement = makeZbStatement([
        'amount_debit' => '100',
    ]);

    expect($statement->amountDebitInUsd())->toBe('3.79');
});

it('returns the original amount when statement currency is not zwg', function () {
    $statement = makeZbStatement([
        'iso_currency_code' => 'USD',
        'amount_credit' => '150.25',
    ]);

    expect($statement->amountCreditInUsd())->toBe('150.25');
});

it('uses latest prior exchange rate when no same-day rate exists', function () {
    FinanceExchangeRate::query()->create([
        'date' => '2026-03-24',
        'currency_from' => 'USD',
        'currency_to' => 'ZWG',
        'rate' => '25.000000',
    ]);
    FinanceExchangeRate::query()->create([
        'date' => '2026-03-25',
        'currency_from' => 'USD',
        'currency_to' => 'ZWG',
        'rate' => '26.380300',
    ]);

    $statement = makeZbStatement([
        'transaction_date' => '2026-03-26T12:00:00',
        'amount_credit' => '26380.30',
    ]);

    expect($statement->amountCreditInUsd())->toBe('1000.00');
    expect($statement->usdConversionRateMetadata())->toMatchArray([
        'rate' => '26.380300',
        'label' => 'ZWG/USD @ 26.380300',
        'date' => '2026-03-25',
    ]);
});

it('returns null when no same-day or prior exchange rate exists for transaction date', function () {
    FinanceExchangeRate::query()->create([
        'date' => '2026-03-27',
        'currency_from' => 'USD',
        'currency_to' => 'ZWG',
        'rate' => '26.380300',
    ]);

    $statement = makeZbStatement([
        'transaction_date' => '2026-03-26T12:00:00',
        'amount_credit' => '26380.30',
    ]);

    expect($statement->amountCreditInUsd())->toBeNull();
});

it('rounds converted amount to two decimal places', function () {
    FinanceExchangeRate::query()->create([
        'date' => '2026-03-25',
        'currency_from' => 'USD',
        'currency_to' => 'ZWG',
        'rate' => '3',
    ]);

    $statement = makeZbStatement([
        'amount_credit' => '10',
    ]);

    expect($statement->amountCreditInUsd())->toBe('3.33');
});

it('returns null for empty amounts', function () {
    FinanceExchangeRate::query()->create([
        'date' => '2026-03-25',
        'currency_from' => 'USD',
        'currency_to' => 'ZWG',
        'rate' => '26.380300',
    ]);

    $statement = makeZbStatement([
        'amount_credit' => '   ',
    ]);

    expect($statement->amountCreditInUsd())->toBeNull();
});

it('returns null conversion rate metadata for non zwg statements', function () {
    $statement = makeZbStatement([
        'iso_currency_code' => 'USD',
        'amount_credit' => '150.25',
    ]);

    expect($statement->usdConversionRateMetadata())->toBeNull();
});
