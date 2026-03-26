<?php

namespace App\Http\Filters\Finance;

use App\Http\Filters\QueryFilter;

class FinanceExchangeRateFilter extends QueryFilter
{
    protected array $sortable = [
        'createdAt' => 'created_at',
        'date' => 'date',
        'currencyFrom' => 'currency_from',
        'currencyTo' => 'currency_to',
        'rate' => 'rate',
        'updatedAt' => 'updated_at',
    ];

    protected array $searchable = ['date', 'currency_from', 'currency_to', 'rate'];
}
