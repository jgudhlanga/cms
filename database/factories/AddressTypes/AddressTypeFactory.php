<?php

namespace Database\Factories\AddressTypes;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AddressTypes\AddressType>
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
