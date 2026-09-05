<?php

declare(strict_types=1);

namespace App\Support\Institution;

final class ProgrammeDurationCalculator
{
    public static function years(
        int $taughtSemesterCount,
        int $attachmentSemesterCount,
        int $periodsPerYear,
        bool $includesIndustrialAttachment,
    ): float {
        $periodsPerYear = max(1, $periodsPerYear);
        $taught = max(0, $taughtSemesterCount);
        $attachment = $includesIndustrialAttachment ? max(0, $attachmentSemesterCount) : 0;

        return round(($taught + $attachment) / $periodsPerYear, 1);
    }
}
