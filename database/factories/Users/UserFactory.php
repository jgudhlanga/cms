<?php

namespace Database\Factories\Users;

use App\Helpers\Helper;
use App\Models\Shared\Gender;
use App\Models\Shared\Title;
use App\Models\Tenants\Tenant;
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
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        return [
            'tenant_id' => function () {
                return Tenant::factory()->create()->id;
            },
            'first_name' => $firstName,
            'middle_name' => fake()->firstName(),
            'last_name' => $lastName,
            'email' => fake()->unique()->safeEmail(),
            'gender_id' => function () {
                return Gender::factory()->create()->id;
            },
            'title_id' => function () {
                return Title::factory()->create()->id;
            },
            'email_verified_at' => now(),
            'password' => Helper::generatePasswordFromName($firstName, $lastName),
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
