<?php

namespace Database\Factories\Institution;

use App\Models\Institution\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word,
        ];
    }
}
