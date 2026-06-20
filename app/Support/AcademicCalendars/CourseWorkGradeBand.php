<?php

namespace App\Support\AcademicCalendars;

use App\Services\AcademicCalendars\CourseWorkAggregationService;

class CourseWorkGradeBand
{
    public const DISTINCTION = 'distinction';

    public const MERIT = 'merit';

    public const PASS = 'pass';

    public const FAIL = 'fail';

    public static function classify(?float $courseWorkTotal60): ?string
    {
        if ($courseWorkTotal60 === null) {
            return null;
        }

        $percent = ($courseWorkTotal60 / CourseWorkAggregationService::COURSEWORK_CAP) * 100;

        if ($percent >= 75) {
            return self::DISTINCTION;
        }

        if ($percent >= 60) {
            return self::MERIT;
        }

        if ($percent >= 50) {
            return self::PASS;
        }

        return self::FAIL;
    }

    public static function isPassing(string $band): bool
    {
        return in_array($band, [self::DISTINCTION, self::MERIT, self::PASS], true);
    }
}
