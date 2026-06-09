<?php

declare(strict_types=1);

namespace App\Enums\Maintenance;

enum MaintenanceApplicationStatusFilterEnum: string
{
    case NO_PROFILE = 'no_profile';
    case NO_PROGRAMMES = 'no_programmes';
    case REVIEW = 'review';
    case WAITLISTED = 'waitlisted';
    case VERIFIED = 'verified';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::NO_PROFILE => __('trans.maintenance_users_status_no_profile'),
            self::NO_PROGRAMMES => __('trans.maintenance_users_status_no_programmes'),
            self::REVIEW => __('trans.maintenance_users_status_review'),
            self::WAITLISTED => __('trans.maintenance_users_status_waitlisted'),
            self::VERIFIED => __('trans.maintenance_users_status_verified'),
            self::UNKNOWN => __('trans.maintenance_users_status_unknown'),
        };
    }

    public static function tryFromFilter(?string $value): ?self
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return self::tryFrom($value);
    }
}
