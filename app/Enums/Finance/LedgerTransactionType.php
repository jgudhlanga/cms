<?php

namespace App\Enums\Finance;

enum LedgerTransactionType: string
{
    case Invoice = 'invoice';
    case Receipt = 'receipt';
    case DebitJournal = 'debit_journal';
    case CreditJournal = 'credit_journal';
    case Refund = 'refund';
    case Adjustment = 'adjustment';
}
