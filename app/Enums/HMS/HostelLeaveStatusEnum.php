<?php

namespace App\Enums\HMS;

enum HostelLeaveStatusEnum: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case DECLINED = 'declined';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('hms.leave_status_pending'),
            self::APPROVED => __('hms.leave_status_approved'),
            self::DECLINED => __('hms.leave_status_declined'),
            self::CANCELLED => __('hms.leave_status_cancelled'),
        };
    }
}
