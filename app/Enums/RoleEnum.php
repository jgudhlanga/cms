<?php

namespace App\Enums;

enum RoleEnum: string
{
    case REGISTRAR = 'Registrar';
    case SELECTION_OFFICER = 'Selection officer';
    case SUPER_ADMINISTRATOR = 'Super Administrator';
    case STUDENT = 'Student';

    // extra helper to allow for greater customization of displayed values, without disclosing the name/value data directly
    public function label(): string
    {
        return match ($this) {
            self::REGISTRAR => 'Registrar',
            self::SELECTION_OFFICER => 'Selection officer',
            self::SUPER_ADMINISTRATOR => 'Super administrator',
            self::STUDENT => 'Student',
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
