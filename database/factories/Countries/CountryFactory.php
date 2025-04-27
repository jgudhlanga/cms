<?php

namespace Database\Factories\Countries;

use App\Models\Countries\Country;
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
