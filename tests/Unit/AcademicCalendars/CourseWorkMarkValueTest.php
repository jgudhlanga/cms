<?php

use App\Support\AcademicCalendars\CourseWorkMarkValue;

test('course work mark value accepts whole numbers from 0 to 100', function (mixed $value, int $expected) {
    expect(CourseWorkMarkValue::tryParse($value))->toBe($expected);
})->with([
    [0, 0],
    [100, 100],
    [72, 72],
    ['85', 85],
    [85.0, 85],
    ['0', 0],
]);

test('course work mark value rejects decimals out of range and non numeric values', function (mixed $value) {
    expect(CourseWorkMarkValue::tryParse($value))->toBeNull();
})->with([
    [72.5],
    [101],
    [-1],
    ['85.5'],
    ['101'],
    ['abc'],
    [true],
    [null],
    [''],
]);
