<?php

namespace Database\Factories\Institution;

use App\Models\Institution\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word,
        ];
    }
}
