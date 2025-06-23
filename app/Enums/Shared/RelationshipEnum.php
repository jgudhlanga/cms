<?php

namespace App\Enums\Shared;

enum RelationshipEnum: string
{
    case PARENT = 'Parent';
    case SPOUSE = 'Spouse';
    case GUARDIAN = 'Guardian';

    public function label(): string
    {
        return match ($this) {
            self::PARENT => 'Parent',
            self::SPOUSE => 'Spouse',
            self::GUARDIAN => 'Guardian',
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
