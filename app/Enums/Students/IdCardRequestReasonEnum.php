<?php

declare(strict_types=1);

namespace App\Enums\Students;

enum IdCardRequestReasonEnum: string
{
    case NEW = 'new';
    case LOST = 'lost';
    case DAMAGED = 'damaged';
    case RENEWAL = 'renewal';

    public function label(): string
    {
        return match ($this) {
            self::NEW => __('students.id_card_reason_new'),
            self::LOST => __('students.id_card_reason_lost'),
            self::DAMAGED => __('students.id_card_reason_damaged'),
            self::RENEWAL => __('students.id_card_reason_renewal'),
        };
    }

    public function requiresFee(): bool
    {
        return $this !== self::NEW;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $reason): array => [
                'value' => $reason->value,
                'label' => $reason->label(),
            ],
            self::cases(),
        );
    }
}
