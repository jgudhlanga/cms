<?php

namespace Database\Factories\Payments;

use App\Models\Payments\PaymentFrequency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentFrequency>
 */
class PaymentFrequencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->word(),
        ];
    }
}
