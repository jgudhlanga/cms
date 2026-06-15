<?php

namespace App\Enums\HMS;

enum HostelEligibilityContextEnum: string
{
    case APPLICATION = 'application';
    case AWAITING_PAYMENT = 'awaiting_payment';
}
