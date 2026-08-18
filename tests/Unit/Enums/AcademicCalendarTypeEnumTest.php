<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;

test('allowed semester slugs follow calendar type year caps', function (): void {
    expect(AcademicCalendarTypeEnum::SEMESTER->allowedSemesterSlugs())->toBe(['semester-1', 'semester-2'])
        ->and(AcademicCalendarTypeEnum::TERM->allowedSemesterSlugs())->toBe(['term-1', 'term-2', 'term-3'])
        ->and(AcademicCalendarTypeEnum::ABMA->allowedSemesterSlugs())->toBe(['abma-1', 'abma-2', 'abma-3', 'abma-4']);
});
