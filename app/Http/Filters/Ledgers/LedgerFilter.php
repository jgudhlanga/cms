<?php

namespace App\Http\Filters\Ledgers;

use App\Http\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

class LedgerFilter extends QueryFilter
{
    protected array $sortable = [
        'createdAt' => 'created_at',
        'systemReference' => 'system_reference',
        'paymentReference' => 'payment_reference',
        'amount' => 'amount',
        'dueDate' => 'due_date',
        'paymentDate' => 'payment_date',
        'type' => 'type',
        'paymentStatus' => 'payment_status',
        'tenant' => 'tenant_id',
        'updatedAt' => 'updated_at'
    ];

    protected array $searchable = ['amount', 'system_reference', 'payment_reference', 'type', 'payment_status' ];


}
