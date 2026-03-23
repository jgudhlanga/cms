<?php

namespace App\Enums\Finance;

enum FinanceAccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Revenue = 'revenue';
    case Expense = 'expense';
    case Equity = 'equity';
}
