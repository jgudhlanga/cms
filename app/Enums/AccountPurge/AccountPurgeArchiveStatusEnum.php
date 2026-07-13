<?php

declare(strict_types=1);

namespace App\Enums\AccountPurge;

enum AccountPurgeArchiveStatusEnum: string
{
    case ACTIVE = 'active';
    case RESTORED = 'restored';
    case FLUSHED = 'flushed';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => __('trans.maintenance_archives_status_active'),
            self::RESTORED => __('trans.maintenance_archives_status_restored'),
            self::FLUSHED => __('trans.maintenance_archives_status_flushed'),
        };
    }

    public static function tryFromFilter(?string $value): ?self
    {
        if ($value === null || trim($value) === '' || $value === 'all') {
            return null;
        }

        return self::tryFrom($value);
    }
}
