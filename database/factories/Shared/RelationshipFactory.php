<?php

namespace Database\Factories\Shared;

use App\Models\Shared\Relationship;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Relationship>
 */
class RelationshipFactory extends Factory
{

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word,
        ];
    }
}
