<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Receipt;
use App\Models\Tenants\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
{
    protected $model = Receipt::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::query()->firstOrFail()->id,
            'student_id' => null,
            'user_id' => null,
            'bank_payment_id' => null,
            'receipt_number' => fake()->unique()->numerify('RCP-########'),
            'amount' => fake()->randomFloat(2, 10, 400),
            'payment_method' => 'bank',
            'payment_date' => now()->toDateString(),
        ];
    }
}
