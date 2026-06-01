<?php

namespace App\Enums\HMS;

enum HostelQueryStatusEnum: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in-progress';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => __('hms.query_status_open'),
            self::IN_PROGRESS => __('hms.query_status_in_progress'),
            self::RESOLVED => __('hms.query_status_resolved'),
            self::CLOSED => __('hms.query_status_closed'),
        };
    }
}
