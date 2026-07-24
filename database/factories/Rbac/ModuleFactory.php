<?php

namespace Database\Factories\Rbac;

use App\Models\Rbac\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    protected $model = Module::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
			'title' => $name = fake()->unique()->word,
			'name' => $name,
			'description' => fake()->paragraph,
        ];
    }
}
