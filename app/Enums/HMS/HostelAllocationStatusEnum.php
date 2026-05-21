<?php

namespace App\Enums\HMS;

enum HostelAllocationStatusEnum: string
{
    case ACTIVE = 'active';
    case CLOSED = 'closed';
    case PENDING = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => __('hms.allocation_status_active'),
            self::CLOSED => __('hms.allocation_status_closed'),
            self::PENDING => __('hms.allocation_status_pending'),
        };
    }
}
