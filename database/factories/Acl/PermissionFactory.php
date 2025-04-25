<?php

namespace Database\Factories\Acl;

use App\Models\Acl\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{

    public function definition(): array
    {
        return [
			'name' => fake()->unique()->word,
			'description' => fake()->paragraph,
        ];
    }
}
