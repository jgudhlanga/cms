<?php

namespace Database\Factories\Shared;

use App\Models\Shared\Gender;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gender>
 */
class GenderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->word,
        ];
    }
}
