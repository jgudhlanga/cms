<?php

namespace Database\Factories\Users;

use App\Models\Genders\Gender;
use App\Models\Tenants\Tenant;
use App\Models\Titles\Title;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => function () {
                return Tenant::factory()->create()->id;
            },
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'gender_id' => function () {
                return Gender::factory()->create()->id;
            },
            'title_id' => function () {
                return Title::factory()->create()->id;
            },
            'email_verified_at' => now(),
            'password' => 'Deve10per!23',
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
