<?php

namespace App\Enums\Shared;

enum CommunicationMethodEnum: string
{
    case EMAIL = 'Email';
    case SMS = 'Sms';
    case PHONE = 'Phone';

    public function label(): string
    {
        return match ($this) {
            self::EMAIL => 'Email',
            self::SMS => 'Sms',
            self::PHONE => 'Phone',
        };
    }

    public static function all(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn ($case) => $case->label(), self::cases())
        );
    }
}
