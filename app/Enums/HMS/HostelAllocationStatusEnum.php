<?php

namespace App\Enums\HMS;

enum HostelAllocationStatusEnum: string
{
    case ACTIVE = 'active';
    case CHECKED_OUT = 'checked-out';
    case CLOSED = 'closed';
    case PENDING = 'pending';

    /**
     * @return list<string>
     */
    public static function indexStatuses(): array
    {
        return [
            self::ACTIVE->value,
            self::CHECKED_OUT->value,
            self::CLOSED->value,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => __('hms.allocation_status_active'),
            self::CHECKED_OUT => __('hms.allocation_status_checked_out'),
            self::CLOSED => __('hms.allocation_status_closed'),
            self::PENDING => __('hms.allocation_status_pending'),
        };
    }
}
