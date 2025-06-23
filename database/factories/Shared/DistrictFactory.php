<?php

namespace Database\Factories\Shared;

use App\Models\Shared\District;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<District>
 */
class DistrictFactory extends Factory
{

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word,
        ];
    }
}
