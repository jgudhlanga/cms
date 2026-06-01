<?php

namespace App\Enums\HMS;

enum HostelNoticeTypeEnum: string
{
    case GENERAL = 'general';
    case EVENT = 'event';
    case URGENT = 'urgent';
    case RULE = 'rule';

    public function label(): string
    {
        return match ($this) {
            self::GENERAL => __('hms.notice_type_general'),
            self::EVENT => __('hms.notice_type_event'),
            self::URGENT => __('hms.notice_type_urgent'),
            self::RULE => __('hms.notice_type_rule'),
        };
    }
}
