<?php

namespace Database\Factories\Finance;

use App\Enums\Finance\LedgerTransactionType;
use App\Models\Finance\LedgerEntry;
use App\Models\Tenants\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerEntry>
 */
class LedgerEntryFactory extends Factory
{
    protected $model = LedgerEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::query()->firstOrFail()->id,
            'student_id' => null,
            'user_id' => null,
            'transaction_type' => LedgerTransactionType::Adjustment,
            'reference_type' => null,
            'reference_id' => null,
            'account_code' => 'AR',
            'debit' => '10.00',
            'credit' => '0.00',
            'transaction_date' => now()->toDateString(),
            'description' => null,
            'currency' => 'USD',
            'exchange_rate' => 1,
        ];
    }
}
