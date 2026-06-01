<?php

namespace App\Enums\Finance;

enum FinanceTransactionQueryStatusEnum: string
{
    case SUBMITTED = 'submitted';
    case NEEDS_INFO = 'needs_info';
    case UNDER_REVIEW = 'under_review';
    case RECONCILED = 'reconciled';
    case DECLINED = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::SUBMITTED => 'Submitted',
            self::NEEDS_INFO => 'Needs Information',
            self::UNDER_REVIEW => 'Under Review',
            self::RECONCILED => 'Reconciled',
            self::DECLINED => 'Declined',
        };
    }
}
