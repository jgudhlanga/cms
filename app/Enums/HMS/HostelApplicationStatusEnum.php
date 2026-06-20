<?php

namespace App\Enums\HMS;

enum HostelApplicationStatusEnum: string
{
    case PENDING = 'pending';
    case AWAITING_PAYMENT = 'awaiting-payment';
    case PARTIALLY_PAID = 'partially-paid';
    case PAID = 'paid';
    case APPROVED = 'approved';
    case DECLINED = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('hms.application_status_pending'),
            self::AWAITING_PAYMENT => __('hms.application_status_awaiting_payment'),
            self::PARTIALLY_PAID => __('hms.application_status_partially_paid'),
            self::PAID => __('hms.application_status_paid'),
            self::APPROVED => __('hms.application_status_approved'),
            self::DECLINED => __('hms.application_status_declined'),
        };
    }
}
