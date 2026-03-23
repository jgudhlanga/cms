<?php

namespace App\Models\Finance;

use App\Enums\Finance\FinanceAccountType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'finance_accounts';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'type' => FinanceAccountType::class,
        ];
    }
}
