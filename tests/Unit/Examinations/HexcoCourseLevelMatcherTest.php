<?php

declare(strict_types=1);

use App\Enums\Institution\LevelEnum;
use App\Support\Examinations\HexcoCourseLevelMatcher;

it('matches the abbreviated course levels HEXCO prints on a statement', function (string $raw, LevelEnum $expected): void {
    expect(HexcoCourseLevelMatcher::match($raw))->toBe($expected);
})->with([
    ['N.C.', LevelEnum::NC],
    ['NC', LevelEnum::NC],
    [' n.c. ', LevelEnum::NC],
    ['N.D.', LevelEnum::ND],
    ['National Diploma', LevelEnum::ND],
    ['H.N.D.', LevelEnum::HND],
    ['HIGHER NATIONAL DIPLOMA', LevelEnum::HND],
    ['B.Tech', LevelEnum::BTECH],
]);

it('returns null for text it does not recognise', function (?string $raw): void {
    expect(HexcoCourseLevelMatcher::match($raw))->toBeNull();
})->with([
    null,
    '',
    '   ',
    'Diploma In Something Else',
    'ABMA Level 4',
]);
