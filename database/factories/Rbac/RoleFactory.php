<?php

namespace Database\Factories\Rbac;

use App\Models\Rbac\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;


    public function definition(): array
    {
        return [
			'name' => fake()->unique()->word,
			'description' => fake()->paragraph,
        ];
    }
}
