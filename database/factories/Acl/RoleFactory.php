<?php

namespace Database\Factories\Acl;

use App\Models\Acl\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{

    public function definition(): array
    {
        return [
			'name' => fake()->unique()->word,
			'description' => fake()->paragraph,
        ];
    }
}
