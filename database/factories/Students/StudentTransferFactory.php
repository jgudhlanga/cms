<?php

declare(strict_types=1);

namespace Database\Factories\Students;

use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentTransfer;
use App\Models\Tenants\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentTransfer>
 */
class StudentTransferFactory extends Factory
{
    protected $model = StudentTransfer::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::query()->value('id') ?? Tenant::factory(),
            'student_id' => Student::factory(),
            'student_application_id' => StudentApplication::factory(),
            'college_name' => fake()->company().' College',
        ];
    }
}
