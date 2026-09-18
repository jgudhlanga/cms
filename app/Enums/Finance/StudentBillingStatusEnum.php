<?php

declare(strict_types=1);

namespace App\Enums\Finance;

enum StudentBillingStatusEnum: string
{
    case EXPORTED = 'exported';
    case BILLED = 'billed';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::EXPORTED => __('finance.billing_export_status_exported'),
            self::BILLED => __('finance.billing_export_status_billed'),
            self::FAILED => __('finance.billing_export_status_failed'),
        };
    }
}
