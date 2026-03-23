<?php

namespace Database\Factories\Finance;

use App\Enums\Finance\InvoiceStatus;
use App\Models\Finance\Invoice;
use App\Models\Tenants\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::query()->firstOrFail()->id,
            'student_id' => null,
            'fee_type_id' => null,
            'invoice_number' => fake()->unique()->numerify('INV-########'),
            'amount' => fake()->randomFloat(2, 50, 500),
            'due_date' => now()->addDays(30),
            'status' => InvoiceStatus::Unpaid,
        ];
    }
}
