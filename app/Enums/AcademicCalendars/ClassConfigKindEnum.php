<?php

declare(strict_types=1);

namespace App\Enums\AcademicCalendars;

enum ClassConfigKindEnum: string
{
    case STANDARD = 'standard';
    case OVERFLOW = 'overflow';
    case REFERRED = 'referred';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::STANDARD => 'Standard',
            self::OVERFLOW => 'Overflow',
            self::REFERRED => 'Referred',
            self::CUSTOM => 'Custom',
        };
    }
}
