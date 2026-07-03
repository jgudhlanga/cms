<?php

namespace App\Enums\Integrations;

use App\Models\HMS\HostelApplication;
use App\Models\Students\ApplicationFee;
use App\Models\Users\User;

enum LedgerEmailSearchTypeEnum: string
{
    case Legacy = 'legacy';
    case ApplicationFee = 'application_fee';
    case HostelApplication = 'hostel_application';

    public function label(): string
    {
        return match ($this) {
            self::Legacy => 'Legacy (User)',
            self::ApplicationFee => 'Application Fee',
            self::HostelApplication => 'Hostel Application',
        };
    }

    public function ledgerableClass(): string
    {
        return match ($this) {
            self::Legacy => User::class,
            self::ApplicationFee => ApplicationFee::class,
            self::HostelApplication => HostelApplication::class,
        };
    }

    public static function tryFromRequest(?string $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::tryFrom($value);
    }
}
