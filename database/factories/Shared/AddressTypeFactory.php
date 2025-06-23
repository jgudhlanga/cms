<?php

namespace Database\Factories\Shared;

use App\Models\Shared\AddressType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AddressType>
 */
class AddressTypeFactory extends Factory
{

    public function definition(): array
    {
        return [
            'title' => $this->faker->word(),
        ];
    }
}
