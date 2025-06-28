<?php

namespace App\Enums\Shared;

enum LanguageEnum: string
{
    case ENGLISH = 'English';

    public function label(): string
    {
        return match ($this) {
            self::ENGLISH => 'English',
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
