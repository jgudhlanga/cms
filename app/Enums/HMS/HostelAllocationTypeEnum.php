<?php

namespace App\Enums\HMS;

enum HostelAllocationTypeEnum: string
{
    case DIRECT = 'direct';
    case APPRENTICE = 'apprentice';
    case GUEST = 'guest';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::DIRECT => __('hms.allocation_type_direct'),
            self::APPRENTICE => __('hms.allocation_type_apprentice'),
            self::GUEST => __('hms.allocation_type_guest'),
            self::OTHER => __('hms.allocation_type_other'),
        };
    }
}
