<?php

namespace Database\Factories\Communications;

use App\Models\Communications\CommunicationMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunicationMethod>
 */
class CommunicationMethodFactory extends Factory
{
	/**
	 * Define the model's default state.
	 *
	 * @return array<string, mixed>
	 */
	public function definition(): array
	{
		return [
			'title' => fake()->unique()->word(),
		];
	}
}
