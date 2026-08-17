<?php

declare(strict_types=1);

namespace Database\Factories\Students;

use App\Models\Students\StudentIdCardSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentIdCardSetting>
 */
class StudentIdCardSettingFactory extends Factory
{
    protected $model = StudentIdCardSetting::class;

    public function definition(): array
    {
        return StudentIdCardSetting::defaultAttributes();
    }
}
