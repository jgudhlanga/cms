<?php

namespace Database\Factories\Rbac;

use App\Models\Rbac\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;


    public function definition(): array
    {
        return [
			'name' => fake()->unique()->word,
			'description' => fake()->paragraph,
        ];
    }
}
