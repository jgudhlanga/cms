<?php

use App\Support\Institution\SyllabusImportCode;

it('splits course and module codes on slash or dot delimiters', function () {
    expect(SyllabusImportCode::segments('553.23.CO.MO'))->toBe(['553', '23', 'CO', 'MO'])
        ->and(SyllabusImportCode::segments('710/24/CO/M0'))->toBe(['710', '24', 'CO', 'M0'])
        ->and(SyllabusImportCode::segments('553/23/M01'))->toBe(['553', '23', 'M01'])
        ->and(SyllabusImportCode::segments('553.23.M01'))->toBe(['553', '23', 'M01']);
});

it('builds equivalent comparison keys across delimiter styles', function () {
    expect(SyllabusImportCode::comparisonKey('553.23.CO.MO'))->toBe('553/23/CO/MO')
        ->and(SyllabusImportCode::comparisonKey('553/23/CO/MO'))->toBe('553/23/CO/MO')
        ->and(SyllabusImportCode::comparisonKey('553.23.M01'))->toBe('553/23/M01')
        ->and(SyllabusImportCode::equivalent('553.23.CO.MO', '553/23/CO/MO'))->toBeTrue()
        ->and(SyllabusImportCode::equivalent('553.23.M01', '553/23/M01'))->toBeTrue();
});

it('derives implementation year from slash or dot course codes', function () {
    expect(SyllabusImportCode::implementationYear('553.23.CO.MO'))->toBe('2023')
        ->and(SyllabusImportCode::implementationYear('710/24/CO/M0'))->toBe('2024')
        ->and(SyllabusImportCode::implementationYear('317/25/CO/MO'))->toBe('2025');
});

it('throws when course code year segment is invalid', function () {
    SyllabusImportCode::implementationYear('INVALID');
})->throws(RuntimeException::class, "Invalid COURSE_CODE 'INVALID'");
