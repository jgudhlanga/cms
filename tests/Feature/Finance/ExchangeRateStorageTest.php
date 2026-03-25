<?php

use App\Models\Finance\FinanceExchangeRate;

it('stores exchange rate date in Y-m-d', function (string $inputDate, string $expectedDate) {
    $record = FinanceExchangeRate::query()->create([
        'date' => $inputDate,
        'currency_from' => 'USD',
        'currency_to' => 'ZWG',
        'rate' => '1.2345',
    ]);

    $fromDb = FinanceExchangeRate::query()->findOrFail($record->id);

    expect($fromDb->date)->toBe($expectedDate);
})->with([
    'yyyy-mm-dd' => ['2026-03-25', '2026-03-25'],
    'dd-mm-yyyy' => ['25-03-2026', '2026-03-25'],
    'dd/mm/yyyy' => ['25/03/2026', '2026-03-25'],
    'yyyy/mm/dd' => ['2026/03/25', '2026-03-25'],
]);

it('preserves exchange rate decimals exactly (including trailing zeros)', function (string $inputRate, string $expectedRate) {
    $record = FinanceExchangeRate::query()->create([
        'date' => '2026-03-25',
        'currency_from' => 'USD',
        'currency_to' => 'ZWG',
        'rate' => $inputRate,
    ]);

    $fromDb = FinanceExchangeRate::query()->findOrFail($record->id);

    expect($fromDb->rate)->toBe($expectedRate);
})->with([
    'simple_decimals' => ['26.3803', '26.3803'],
    'trailing_zeros' => ['26.380300', '26.380300'],
    'small_decimal' => ['0.0001', '0.0001'],
    'trims_whitespace' => ['  26.3803  ', '26.3803'],
]);
