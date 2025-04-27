<?php

namespace App\Enums;

enum RoleEnum: string
{
    case ACCOUNTANT = 'Accountant';
    case AUDITOR = 'Auditor';
    case SUPER_ADMINISTRATOR = 'Super Administrator';

    // extra helper to allow for greater customization of displayed values, without disclosing the name/value data directly
    public function label(): string
    {
        return match ($this) {
            self::ACCOUNTANT => 'Accountant',
            self::AUDITOR => 'Auditor',
            self::SUPER_ADMINISTRATOR => 'Super administrator',
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
