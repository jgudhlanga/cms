<?php

namespace Database\Factories\Statuses;

use App\Models\Statuses\MaritalStatus;
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
