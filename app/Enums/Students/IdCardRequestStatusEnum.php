<?php

declare(strict_types=1);

namespace App\Enums\Students;

enum IdCardRequestStatusEnum: string
{
    case AWAITING_PAYMENT = 'awaiting_payment';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PRINTED = 'printed';
    case ISSUED = 'issued';

    public function label(): string
    {
        return match ($this) {
            self::AWAITING_PAYMENT => __('students.id_card_status_awaiting_payment'),
            self::PENDING => __('students.id_card_status_pending'),
            self::APPROVED => __('students.id_card_status_approved'),
            self::REJECTED => __('students.id_card_status_rejected'),
            self::PRINTED => __('students.id_card_status_printed'),
            self::ISSUED => __('students.id_card_status_issued'),
        };
    }

    /**
     * @return list<self>
     */
    public static function activeStatuses(): array
    {
        return [
            self::AWAITING_PAYMENT,
            self::PENDING,
            self::APPROVED,
            self::PRINTED,
        ];
    }

    public function isActive(): bool
    {
        return in_array($this, self::activeStatuses(), true);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases(),
        );
    }
}
