<?php

namespace App\Enums\HMS;

enum HostelNoticeStatusEnum: string
{
    case PENDING = 'pending';
    case PUBLISHED = 'published';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('hms.notice_status_pending'),
            self::PUBLISHED => __('hms.notice_status_published'),
            self::CANCELLED => __('hms.notice_status_cancelled'),
            self::EXPIRED => __('hms.notice_status_expired'),
        };
    }
}
