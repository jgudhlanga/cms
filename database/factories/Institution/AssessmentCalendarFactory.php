<?php

namespace Database\Factories\Institution;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentCalendar>
 */
class AssessmentCalendarFactory extends Factory
{
    protected $model = AssessmentCalendar::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'assessment_type_id' => AssessmentType::factory(),
            'academic_calendar_id' => AcademicCalendar::query()->create([
                'calendar_year' => (string) fake()->year(),
                'type' => AcademicCalendarTypeEnum::SEMESTER->value,
                'opening_date' => $startDate->format('Y-m-d'),
                'closing_date' => fake()->dateTimeBetween($startDate, '+6 months')->format('Y-m-d'),
            ])->id,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => fake()->dateTimeBetween($startDate, '+3 months')->format('Y-m-d'),
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        ];
    }
}
