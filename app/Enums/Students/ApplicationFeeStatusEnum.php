<?php

namespace App\Enums\Students;

enum ApplicationFeeStatusEnum: string
{
    case AWAITING_PAYMENT = 'awaiting-payment';
    case PAID = 'paid';
    case SUBMITTED = 'submitted';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::AWAITING_PAYMENT => __('trans.application_fee_status_awaiting_payment'),
            self::PAID => __('trans.application_fee_status_paid'),
            self::SUBMITTED => __('trans.application_fee_status_submitted'),
            self::CANCELLED => __('trans.application_fee_status_cancelled'),
        };
    }
}
