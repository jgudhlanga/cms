<?php

namespace App\Enums\Shared;

enum IdTypeEnum: string
{
    case ZIMBABWEAN_ID_NUMBER = 'Zimbabwean National ID';
    case FOREIGN_PASSPORT_NUMBER = 'Foreign Passport Number';

    public function id(): int
    {
        return match ($this) {
            self::ZIMBABWEAN_ID_NUMBER => 1,
            self::FOREIGN_PASSPORT_NUMBER => 2,
        };
    }

    public function label(): string
    {
        return $this->value;
    }
    public function isDefault(): bool
    {
        return match ($this) {
            self::ZIMBABWEAN_ID_NUMBER => true,
            self::FOREIGN_PASSPORT_NUMBER => false,
        };
    }
    public function description(): string
    {
        return match ($this) {
            self::ZIMBABWEAN_ID_NUMBER => 'A valid Zimbabwean National Identification Number issued by the Registrar General’s Office.',
            self::FOREIGN_PASSPORT_NUMBER => 'A valid passport number issued by a foreign government, subject to verification and approval.',
        };
    }

    public static function byId(int $id): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->id() === $id) {
                return $case;
            }
        }
        return null;
    }
}
