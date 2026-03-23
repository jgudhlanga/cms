<?php

namespace Database\Factories\Finance;

use App\Enums\Finance\FinanceAccountType;
use App\Models\Finance\Account;
use App\Models\Tenants\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::query()->firstOrFail()->id,
            'code' => fake()->unique()->bothify('ACC-####'),
            'name' => fake()->words(3, true),
            'type' => FinanceAccountType::Asset,
        ];
    }
}
