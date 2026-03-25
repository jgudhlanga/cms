<?php

use App\Models\Integrations\Banks\BankPayment;

it('returns USD for configured USD sources', function (string $source) {
    $payment = new BankPayment([
        'source' => $source,
    ]);

    expect($payment->currency)->toBe('USD');
})->with([
    '4144796412405',
    '4144796412081',
]);

it('returns ZWG for configured ZWG source', function () {
    $payment = new BankPayment([
        'source' => '4144796412082',
    ]);

    expect($payment->currency)->toBe('ZWG');
});

it('returns null currency when source is missing or unknown', function (?string $source) {
    $payment = new BankPayment([
        'source' => $source,
    ]);

    expect($payment->currency)->toBeNull();
})->with([
    null,
    '',
    '   ',
    'unknown-source',
]);
