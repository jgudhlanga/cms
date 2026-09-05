<?php

declare(strict_types=1);

use App\Support\Institution\ProgrammeDurationCalculator;

it('keeps duration as taught time when industrial attachment is off', function (): void {
    expect(ProgrammeDurationCalculator::years(2, 2, 2, false))->toBe(1.0)
        ->and(ProgrammeDurationCalculator::years(3, 0, 2, false))->toBe(1.5);
});

it('adds attachment time into duration while leaving period counts with the caller', function (): void {
    expect(ProgrammeDurationCalculator::years(2, 2, 2, true))->toBe(2.0)
        ->and(ProgrammeDurationCalculator::years(3, 2, 2, true))->toBe(2.5)
        ->and(ProgrammeDurationCalculator::years(3, 3, 3, true))->toBe(2.0);
});
