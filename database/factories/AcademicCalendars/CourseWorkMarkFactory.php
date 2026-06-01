<?php

namespace Database\Factories\AcademicCalendars;

use App\Models\AcademicCalendars\CourseWorkMark;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseWorkMark>
 */
class CourseWorkMarkFactory extends Factory
{
    protected $model = CourseWorkMark::class;

    public function definition(): array
    {
        return [
            'mark' => fake()->numberBetween(0, 100),
            'remark' => fake()->optional()->sentence(),
        ];
    }
}
