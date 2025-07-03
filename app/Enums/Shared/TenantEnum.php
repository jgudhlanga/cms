<?php

namespace App\Enums\Shared;

enum TenantEnum: string
{
    case HARARE_POLY = 'Harare Poly';
    case PENSTEJ_SYSTEMS = 'Penstej Systems';

    public function id(): int
    {
        return match ($this) {
            self::HARARE_POLY => 1,
            self::PENSTEJ_SYSTEMS => 2,
        };
    }

    public static function byId(int $id): ?self
    {
        return collect(self::cases())
            ->first(fn(self $case) => $case->id() === $id);
    }

    public static function all(): array
    {
        return array_map(
            fn(self $case) => [
                'id' => $case->id(),
                'name' => $case->name,   // enum case name: HARARE_POLY
                'value' => $case->value, // enum value: 'Harare Poly'
            ],
            self::cases()
        );
    }
}


