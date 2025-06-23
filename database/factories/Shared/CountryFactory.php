<?php

namespace Database\Factories\Shared;

use App\Models\Shared\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->country(),
            'flag' => $this->faker->imageUrl(),
        ];
    }
}
