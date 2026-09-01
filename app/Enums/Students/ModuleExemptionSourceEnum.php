<?php

declare(strict_types=1);

namespace App\Enums\Students;

enum ModuleExemptionSourceEnum: string
{
    case HEXCO_ZNQF = 'hexco_znqf';

    public function label(): string
    {
        return match ($this) {
            self::HEXCO_ZNQF => 'HEXCO / ZNQF',
        };
    }
}
