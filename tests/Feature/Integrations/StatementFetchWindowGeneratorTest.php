<?php

use App\Services\Integrations\Banks\ZB\StatementFetchWindowGenerator;
use Carbon\CarbonImmutable;

it('splits a month into inclusive two-week windows using chunk_days from config', function () {
    config()->set('custom.bank-statements.chunk_days', 14);

    $generator = app(StatementFetchWindowGenerator::class);

    $windows = $generator->windowsBetween(
        CarbonImmutable::parse('2026-01-01', 'Africa/Harare'),
        CarbonImmutable::parse('2026-01-31', 'Africa/Harare'),
    );

    expect($windows)->toHaveCount(3)
        ->and($windows[0])->toBe(['start' => '2026-01-01', 'end' => '2026-01-14'])
        ->and($windows[2])->toBe(['start' => '2026-01-29', 'end' => '2026-01-31']);
});

it('returns an empty list when the end date is before the start date', function () {
    $generator = app(StatementFetchWindowGenerator::class);

    $windows = $generator->windowsBetween(
        CarbonImmutable::parse('2026-02-01'),
        CarbonImmutable::parse('2026-01-01'),
    );

    expect($windows)->toBe([]);
});
