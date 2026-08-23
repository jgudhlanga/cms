<?php

use App\Support\Institution\DepartmentColorPalette;

it('returns the first unused palette color', function () {
    expect(DepartmentColorPalette::nextColor([]))->toBe('#2563EB')
        ->and(DepartmentColorPalette::nextColor(['#2563EB']))->toBe('#DC2626');
});

it('generates a distinct shade when the base palette is exhausted', function () {
    $used = DepartmentColorPalette::COLORS;
    $next = DepartmentColorPalette::nextColor($used);

    expect($next)->not->toBeIn(array_map(
        static fn (string $color): string => DepartmentColorPalette::normalize($color),
        $used,
    ))
        ->and(DepartmentColorPalette::isValid($next))->toBeTrue();
});

it('never returns a duplicate even when many colors are already used', function () {
    $used = [];

    for ($index = 0; $index < 180; $index++) {
        $next = DepartmentColorPalette::nextColor($used);

        expect($used)->not->toContain($next)
            ->and(DepartmentColorPalette::isValid($next))->toBeTrue();

        $used[] = $next;
    }
});

it('validates hex color codes', function () {
    expect(DepartmentColorPalette::isValid('#2563EB'))->toBeTrue()
        ->and(DepartmentColorPalette::isValid('2563EB'))->toBeFalse()
        ->and(DepartmentColorPalette::isValid('#GGGGGG'))->toBeFalse();
});

it('normalizes color codes to uppercase', function () {
    expect(DepartmentColorPalette::normalize('#abcdef'))->toBe('#ABCDEF');
});
