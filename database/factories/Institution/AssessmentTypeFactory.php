<?php

namespace Database\Factories\Institution;

use App\Models\Institution\AssessmentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentType>
 */
class AssessmentTypeFactory extends Factory
{
    protected $model = AssessmentType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'modes_of_study' => [],
            'description' => fake()->sentence(),
        ];
    }
}
