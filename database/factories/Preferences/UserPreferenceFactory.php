<?php

namespace Database\Factories\Preferences;

use App\Models\Preferences\UserPreference;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserPreference>
 */
class UserPreferenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'side_bar_state' => $this->faker->boolean(),
            'locale' => 'en',
        ];
    }
}
