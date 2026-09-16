<?php

declare(strict_types=1);

namespace App\Enums\Setup;

enum SetupGapSeverityEnum: string
{
    case CRITICAL = 'critical';
    case WARNING = 'warning';
    case INFO = 'info';

    /**
     * Ordering weight for lists and for deciding the badge the header alert shows.
     */
    public function weight(): int
    {
        return match ($this) {
            self::CRITICAL => 3,
            self::WARNING => 2,
            self::INFO => 1,
        };
    }
}
