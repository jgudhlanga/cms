<?php

namespace App\Enums\HMS;

enum HostelQueryPriorityEnum: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';

    public function label(): string
    {
        return match ($this) {
            self::LOW => __('hms.query_priority_low'),
            self::MEDIUM => __('hms.query_priority_medium'),
            self::HIGH => __('hms.query_priority_high'),
        };
    }
}
