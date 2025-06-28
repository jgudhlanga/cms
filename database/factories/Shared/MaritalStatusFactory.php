<?php

namespace Database\Factories\Shared;

use App\Models\Shared\MaritalStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaritalStatus>
 */
class MaritalStatusFactory extends Factory
{

    public function definition(): array
    {
        return [
            'title' => fake()->unique()->word,
        ];
    }
}
