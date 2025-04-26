<?php

namespace Database\Factories\Institution;

use App\Models\Institution\ModeOfStudy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModeOfStudy>
 */
class ModeOfStudyFactory extends Factory
{

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word,
        ];
    }
}
