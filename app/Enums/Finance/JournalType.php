<?php

namespace App\Enums\Finance;

enum JournalType: string
{
    case Debit = 'debit';
    case Credit = 'credit';
    case Adjustment = 'adjustment';
}
