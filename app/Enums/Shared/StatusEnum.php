<?php

namespace App\Enums\Shared;

enum StatusEnum: string
{
    case ACTIVE = 'Active';
    case WAITING_APPROVAL = 'Waiting Approval';
    case INACTIVE = 'Inactive';

    public function id(): int
    {
        return match ($this) {
            self::ACTIVE => 1,
            self::WAITING_APPROVAL => 2,
            self::INACTIVE => 3,
        };
    }

    public function label(): string
    {
        return $this->value;
    }

    public function description(): string
    {
        return match ($this) {
            self::ACTIVE => 'Currently active and in use',
            self::WAITING_APPROVAL => 'Pending approval from an authority',
            self::INACTIVE => 'Not currently active',
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
