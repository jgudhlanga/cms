<?php

namespace Database\Factories\Finance;

use App\Enums\Finance\JournalType;
use App\Models\Finance\Journal;
use App\Models\Tenants\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Journal>
 */
class JournalFactory extends Factory
{
    protected $model = Journal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::query()->firstOrFail()->id,
            'journal_type' => JournalType::Adjustment,
            'description' => fake()->sentence(),
            'journal_date' => now()->toDateString(),
            'created_by' => null,
        ];
    }
}
