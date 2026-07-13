<?php

namespace App\Enums\Integrations;

enum PaymentCurrencyCodeEnum: string
{
    case Usd = '840';
    case Zwg = '924';

    public function selectionValue(): string
    {
        return match ($this) {
            self::Usd => 'usd',
            self::Zwg => 'zwg',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Usd => 'USD',
            self::Zwg => 'ZWG',
        };
    }

    public static function tryFromSelection(?string $value): ?self
    {
        return match (strtolower((string) $value)) {
            'usd' => self::Usd,
            'zwg' => self::Zwg,
            default => null,
        };
    }

    public static function tryFromCode(?string $code): ?self
    {
        return self::tryFrom((string) $code);
    }
}
