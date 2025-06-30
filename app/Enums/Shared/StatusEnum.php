<?php

namespace App\Enums\Shared;

enum StatusEnum: string
{
    case ACTIVE = 'Active';
    case WAITING_APPROVAL = 'Waiting Approval';
    case INACTIVE = 'Inactive';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::WAITING_APPROVAL => 'Waiting Approval',
            self::INACTIVE => 'Inactive',
        };
    }

    public static function all(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
