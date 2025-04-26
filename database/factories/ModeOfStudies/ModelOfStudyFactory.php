<?php

namespace Database\Factories\ModeOfStudies;

use App\Models\ModeOfStudies\ModelOfStudy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModelOfStudy>
 */
class ModelOfStudyFactory extends Factory
{

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word,
        ];
    }
}
