<?php

namespace App\Enums;

enum DivisionEnum: string
{
    case BUSINESS = 'Business';
    case MANAGEMENT = 'Management';
    case PEDAGOGICS = 'Pedagogics';

    public function label(): string
    {
        return match ($this) {
            self::BUSINESS => 'Business',
            self::MANAGEMENT => 'Management',
            self::PEDAGOGICS => 'Pedagogics',
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
