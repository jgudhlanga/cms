<?php

namespace Database\Factories\Examinations;

use App\Models\Examinations\ExaminationImport;
use App\Models\Examinations\ExaminationResult;
use App\Models\Tenants\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExaminationResult>
 */
class ExaminationResultFactory extends Factory
{
    protected $model = ExaminationResult::class;

    public function definition(): array
    {
        $session = (string) fake()->numberBetween(43000, 45000);

        return [
            'tenant_id' => Tenant::factory(),
            'examination_import_id' => ExaminationImport::factory(),
            'student_id' => null,
            'discipline' => 'Automotive',
            'course_code' => '306/13/CR/0',
            'candidate_number' => fake()->unique()->numerify('1117001D#####'),
            'surname' => fake()->lastName(),
            'first_names' => fake()->firstName(),
            'subject_code' => fake()->unique()->bothify('306/13/S##'),
            'subject' => fake()->words(4, true),
            'grade' => fake()->randomElement(['A', 'B', 'C', 'D', 'P']),
            'session' => $session,
            'session_date' => null,
            'course_comment' => 'AWARD',
        ];
    }
}
