<?php

namespace App\Enums\Shared;

enum ReligionEnum: string
{
    case CHRISTIANITY = 'Christianity';
    case ATR = 'African Traditional Religion';
    case ISLAM = 'Islam';
    case HINDUISM = 'Hinduism';
    case BUDDHISM = 'Buddhism';
    case JUDAISM = 'Judaism';
    case OTHER_RELIGIONS = 'Other Religions';
    case RELIGIOUSLY_UNAFFILIATED = 'Religiously Unaffiliated';

    public function label(): string
    {
        return match ($this) {
            self::CHRISTIANITY => 'Christianity',
            self::ATR => 'African Traditional Religion',
            self::ISLAM => 'Islam',
            self::HINDUISM => 'Hinduism',
            self::BUDDHISM => 'Buddhism',
            self::JUDAISM => 'Judaism',
            self::OTHER_RELIGIONS => 'Other Religions',
            self::RELIGIOUSLY_UNAFFILIATED => 'Religiously Unaffiliated',
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
