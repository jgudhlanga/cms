<?php

namespace App\Enums\HMS;

enum HostelQueryCategoryEnum: string
{
    case MAINTENANCE = 'maintenance';
    case PLUMBING = 'plumbing';
    case ELECTRICAL = 'electrical';
    case CLEANLINESS = 'cleanliness';
    case SECURITY = 'security';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::MAINTENANCE => __('hms.query_category_maintenance'),
            self::PLUMBING => __('hms.query_category_plumbing'),
            self::ELECTRICAL => __('hms.query_category_electrical'),
            self::CLEANLINESS => __('hms.query_category_cleanliness'),
            self::SECURITY => __('hms.query_category_security'),
            self::OTHER => __('hms.query_category_other'),
        };
    }
}
