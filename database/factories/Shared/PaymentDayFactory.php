<?php

namespace Database\Factories\Shared;

use App\Models\Shared\PaymentDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentDay>
 */
class PaymentDayFactory extends Factory
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
